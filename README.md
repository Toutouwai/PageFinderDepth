# PageFinder Depth

Adds the ability to find and sort pages by the depth of the page relative to the home page. The module requires that the core PagePaths module is installed.

Depth of a page in this case means the same thing as "number of parents", so a page that is directly under the home page has a depth of 1, and a child of that page has a depth of 2, and so on.

If you already have a Page object you can get its depth with `$page->numParents()` but the result of this isn't searchable in a PageFinder selector. Installing this module allows you to use selectors like this:

```php
$items = $pages->find("depth=2");

$items = $pages->find("template=basic-page, depth>1, depth<4");

$items = $pages->find("template=product, sort=depth");
```

The keyword "depth" is configurable in the module settings so you can change it to something different if you already have a field named "depth" that the default keyword would clash with.

## Searching by depth in an existing PageArray

This module only adds features for PageFinder selectors and doesn't automatically add any depth property to Page objects in memory, but you can search within a PageArray using `numParents` to achieve the same thing, e.g.

```php
$items = $my_pagearray->find("template=basic-page, numParents>1, numParents<4");
```



