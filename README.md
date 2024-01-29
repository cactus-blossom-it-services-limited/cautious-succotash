# cautious-succotash
test
## Snapshot
- Use the ddev snapshot command `ddev snapshot restore --latest` to restore the database from the snapshot
- The snapshots are inside the .ddev/db_snapshots folder
## Defects
- There are several things not completed
- The recipes full page view mode displays the related cooking lesson products twice
- This is a bug that may be related to the fact that recipes and lessons are a many-to-many relationship
- So when in the view block in the full page view mode queries the recipes entity reference field it may be finding 2x instances of the product entity target id
- If I had more time I would investigate [these examples](https://docs.drupalcommerce.org/commerce2/developer-guide/core/libraries-and-dependencies/state-machine/code-recipes) for a code fix
- The 'Add to cart' button is not displayed on the recipe full page view.
- This should be solvable using the [documentation](https://docs.drupalcommerce.org/commerce2/developer-guide/products/displaying-products/add-to-cart-form)
- I have created two exposed filters but a bug seems to be causing 'autocomplete' to be used instead of the configured 'select'
## Incomplete
- I have created a custom module
### Tasks planned but not started
- The lesson of the day requires a simple block plugin implementation
- You can limit (in code) the block to display only on the home page
- The rules module could be used to schedule the change of product at midnight
- A cron job could also be created using hook_cron
- There is a 'random' sort option in views core that could be used
- Or in the cron job Entity Query could be used to query all the entities of the product type
- Then all except one (randomly) could be unpublished
- I believe that the 'cascade' of the exposed filters is achievable using 'grouping'
- The API endpoint would use the 'json' type of view display
- This could be done in code by extending a views plugin
