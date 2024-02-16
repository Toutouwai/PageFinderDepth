<?php namespace ProcessWire;

class PageFinderDepth extends WireData implements Module, ConfigurableModule {

	protected $modify;

	protected $clauses = [
		'select' => '',
		'join' => '',
		'groupbys' => [],
		'orderby' => '',
	];

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct();
		$this->keyword = 'depth';
	}

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHook('PageFinder::getQuery', $this, 'hookPageFinderQuery', ['before' => true, 'after' => true]);
	}

	/**
	 * Hook before and after PageFinder::getQuery
	 *
	 * @param HookEvent $event
	 */
	protected function hookPageFinderQuery(HookEvent $event) {
		/** @var Selectors $selectors */
		$selectors = $event->arguments(0);
		/** @var DatabaseQuerySelect $query */
		$query = $event->return;

		// Before hook
		if($event->when === 'before') {
			$this->modify = false;
			$this->clauses = [
				'select' => '',
				'join' => '',
				'groupbys' => [],
				'orderby' => '',
			];
			foreach($selectors as $selector) {

				// Selector must include field and value
				if(!isset($selector['field']) || !isset($selector['value'])) continue;

				// Selector field is sort
				if($selector['field'] === 'sort') {
					$trimmed_value = trim($selector['value'], '-+');
					// Skip if trimmed value is not depth
					if($trimmed_value !== $this->keyword) continue;
					$this->modify = true;
					$fc = substr($selector['value'], 0, 1);
					$selector['value'] = $trimmed_value;
					$order = $fc === '-' ? 'DESC' : 'ASC';
					$this->clauses['select'] = "LENGTH(pages_paths.path) - LENGTH(REPLACE(pages_paths.path, '/', '')) AS depth";
					$this->clauses['join'] = "pages_paths ON pages_paths.pages_id = pages.id";
					$this->clauses['orderby'] = "depth $order";
					$selectors->remove($selector);
				}

				// Selector field is depth
				elseif($selector['field'] === $this->keyword) {
					// Selector value must not be an array
					if(is_array($selector['value'])) continue;
					$this->modify = true;
					$value = (int) $selector['value'];
					// A depth value of zero is not supported and will be treated the same as a value of 1
					// Deduct 1 from value so that a depth of 1 means one level below home
					if($value > 0) --$value;
					$operator = $selector::getOperator();
					$this->clauses['select'] = "LENGTH(pages_paths.path) - LENGTH(REPLACE(pages_paths.path, '/', '')) AS depth";
					$this->clauses['join'] = "pages_paths ON pages_paths.pages_id = pages.id";
					$this->clauses['groupbys'][] = "HAVING depth $operator $value";
					$selectors->remove($selector);
				}

			}
		}

		// After hook and event return should be modified
		elseif($this->modify) {
			if($this->clauses['select']) $query->select($this->clauses['select']);
			if($this->clauses['join']) $query->join($this->clauses['join']);
			foreach($this->clauses['groupbys'] as $groupby) {
				$query->groupby($groupby);
			}
			if($this->clauses['orderby']) $query->orderby($this->clauses['orderby']);
			$event->return = $query;
		}
	}

	/**
	 * Config inputfields
	 *
	 * @param InputfieldWrapper $inputfields
	 */
	public function getModuleConfigInputfields($inputfields) {
		$modules = $this->wire()->modules;

		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f_name = 'keyword';
		$f->name = $f_name;
		$f->label = $this->_('Depth keyword');
		$f->description = $this->_('The selector keyword that represents depth. This is configurable in case your site is already using a field named "depth".');
		$f->value = $this->$f_name;
		$inputfields->add($f);
	}

}
