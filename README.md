# cautious-succotash
test
## Snapshot
Use the ddev snapshot command to restore the database from the snapshot
The snapshot is inside the .ddev/db_snapshots folder
## Defects
- There are several things not completed
- The recipes full page view mode displays the related cooking lesson products twice
- This is a bug that may be related to the fact that recipes and lessons are a many-to-many relationship
- So when in the view block in the full page view mode queries the recipes entity reference field it may be finding 2x instances of the product entity target id
- The 'Add to cart' button is not displayed on the recipe full page view.
- This should be solvable using the [documentation](https://docs.drupalcommerce.org/commerce2/developer-guide/products/displaying-products/add-to-cart-form)
- ## Incomplete
- 
