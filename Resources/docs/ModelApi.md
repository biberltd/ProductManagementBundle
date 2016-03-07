Product Management Model API
========================================
**File**: Services\ProductManagementModel.php
**Namespace**: BiberLtd\Bundle\ProductManagementBundle\Services\ProductManagementModel

## USAGE

As Symfony Service:

$model = this->kernel->getContainer()->get('productmanagement.model');

As Direct Object:

$model = new \BiberLtd\Bundle\ProductManagementBundle\Services\ProuctManagementModel($kernel, 'dbconnection', 'orm');

## API

### getProduct(_mixed_ $product)
This method is used to fetch single product from Database.

**Parameters**:
- __mixed_ $product

	Product entity, id as integer,,m, url key as string or sku as string.

**Return**:
- ModelResponse

	Result set will either have NULL or Product entity.

**Usage**:

	$response = $model->getProduct(3);
	if(!response->error->exist){
		$product = $response->result->set;
	}
