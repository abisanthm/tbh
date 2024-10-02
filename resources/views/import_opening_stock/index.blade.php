@extends('layouts.app')
@section('title', __('lang_v1.import_opening_stock'))

@section('content')
<br/>
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Add Production Stock</h1>
</section>

<!-- Main content -->
<section class="content">
    @if (session('notification') || !empty($notification))
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                @if(!empty($notification['msg']))
                    {{$notification['msg']}}
                @elseif(session('notification.msg'))
                    {{ session('notification.msg') }}
                @endif
              </div>
          </div>  
      </div>     
    @endif

    <div class="row">
        <!-- Filter by Category -->
        <div class="col-md-7">
            <div class="form-group">
                <div class="btn-group btn-group-toggle" data-toggle="buttons" style="display: flex; flex-wrap: wrap; justify-content: space-between; margin-bottom: 20px;">
                    <!-- Button for All Categories -->
                    <label class="btn btn-secondary active filter-button" data-filter="all" style="padding: 10px; border-radius: 4px; cursor: pointer; display: inline-block; flex: 0 0 23%; text-align: center; margin-bottom: 10px;">
                        <input type="radio" name="categoryFilter" value="all" autocomplete="off" checked> All
                    </label>
                
                    <!-- Loop through categories and display each as a button -->
                    @foreach($categories as $category)
                        <label class="btn btn-sm btn-primary filter-button" data-filter="{{ $category->id }}" style="padding: 10px; border-radius: 4px; cursor: pointer; display: inline-block; flex: 0 0 23%; text-align: center; margin-bottom: 10px;">
                            <input type="radio" name="categoryFilter" value="{{ $category->id }}" autocomplete="off"> {{ $category->name }}
                        </label>
                    @endforeach
                </div>    
            </div>


            <div id="productList" class="row tw-overflow-x-auto" style="display: none">
                @foreach($products as $product)
                    <div class="col-md-3 mb-3 product-item" data-category="{{ $product->category_id }}" style="padding: 10px; flex: 0 0 23%; box-sizing: border-box; margin-bottom: 20px;">
                        <button type="button" style="padding: 30px" class="add-product" data-name="{{ $product->name }}" data-unit_cost="{{ $product->variations->first()->default_sell_price }}" style="width: 100%; padding: 15px; border-radius: 5px;">
                            <div style="font-size: 1.2em; font-weight: bold;">{{ $product->name }}</div>
                        </button>
                    </div>                
                @endforeach
            </div>
        </div>
        
        <!-- Product Table -->
        <div class="col-md-5">
            @component('components.widget', ['class' => 'box-primary'])
                {!! Form::open(['url' => action([\App\Http\Controllers\ImportOpeningStockController::class, 'store']), 'method' => 'post']) !!}
                
                <!-- Product Table -->
                <table id="productsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('product.product_name')</th>
                            <th>Qty</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-success">@lang('messages.submit')</button>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Product List Modal -->
<div class="modal fade" id="productListModal" tabindex="-1" role="dialog" aria-labelledby="productListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productListModalLabel">Products in Selected Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Product List -->
                <div id="filteredProductList" class="row tw-overflow-x-auto"></div>

                <!-- Quantity Modal (part of product list modal) -->
                <div id="quantityModalContent" style="display: none; margin-top: 20px;">
                    <h5>Enter Quantity</h5>
                    <form id="quantityForm">
                        <div class="form-group">
                            <label for="modal-product-name">Product</label>
                            <input type="text" id="modal-product-name" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="modal-product-quantity">Quantity</label>
                            <input type="number" id="modal-product-quantity" class="form-control" min="1" required>
                        </div>
                        <input type="hidden" id="modal-product-unit-cost">
                        <button type="button" id="addProductToTable" class="btn btn-primary">Add Product</button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>




<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    var rowIdx = 1;
    var selectedProduct = null;

    // Handle category filter click and show filtered products in the modal
    $('.filter-button').click(function() {
        var selectedCategory = $(this).data('filter');
        var filteredProducts = '';

        if (selectedCategory === 'all') {
            // Show all products
            $('.product-item').each(function() {
                var productName = $(this).find('.add-product').data('name');
                var productUnitCost = $(this).find('.add-product').data('unit_cost');

                filteredProducts += `
                    <div class="col-md-3 mb-3">
                        <button type="button" class="btn btn-sm btn-primary select-product" data-name="${productName}" data-unit_cost="${productUnitCost}">
                            <div>${productName}</div>
                        </button>
                    </div>
                `;
            });
        } else {
            // Show products from the selected category
            $('.product-item[data-category="' + selectedCategory + '"]').each(function() {
                var productName = $(this).find('.add-product').data('name');
                var productUnitCost = $(this).find('.add-product').data('unit_cost');

                filteredProducts += `
                    <div class="col-md-3 mb-3">
                        <button type="button" style="margin-top:5px" class="btn btn-lg btn-block btn-primary select-product" data-name="${productName}" data-unit_cost="${productUnitCost}">
                            <div>${productName}</div>
                        </button>
                    </div>
                `;
            });
        }

        // Append the filtered products to the modal and show it
        $('#filteredProductList').html(filteredProducts);
        $('#productListModal').modal('show');
    });

    // When a product is selected, show the quantity modal below
    $(document).on('click', '.select-product', function() {
        selectedProduct = {
            name: $(this).data('name'),
            unitCost: $(this).data('unit_cost')
        };

        // Set product details in the quantity form
        $('#modal-product-name').val(selectedProduct.name);
        $('#modal-product-unit-cost').val(selectedProduct.unitCost);

        // Show the quantity form below the product list
        $('#quantityModalContent').slideDown();
    });

    // Handle adding product to table from the quantity form
    $('#addProductToTable').click(function() {
        addProductToTable();
    });

    // Function to add product to the table
    function addProductToTable() {
        var quantity = $('#modal-product-quantity').val();

        if (quantity && selectedProduct) {
            // Check if product already exists in the table
            var productExists = false;
            $('#productsTable tbody tr').each(function() {
                if ($(this).find('.product-name').val() === selectedProduct.name) {
                    productExists = true;
                    return false;
                }
            });

            // Add product to table if it does not already exist
            if (!productExists) {
                var newRow = `
                    <tr>
                        <td><input type="text" name="products[${rowIdx}][name]" class="product-name form-control" value="${selectedProduct.name}" required readonly></td>
                        <td><input type="number" name="products[${rowIdx}][quantity]" class="form-control product-quantity" value="${quantity}" required></td>
                        <td><input type="hidden" name="products[${rowIdx}][unit_cost]" value="${selectedProduct.unitCost}"></td>
                        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
                    </tr>
                `;
                $('#productsTable tbody').append(newRow);
                rowIdx++;

                // Reset quantity form and hide it after adding the product
                $('#quantityForm')[0].reset();
                $('#quantityModalContent').slideUp();
            }
        }
    }

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Show only latest 9 products by default
    function showLatestProducts() {
        var products = $('.product-item');
        products.hide(); // Hide all products
        products.slice(0, 9).show(); // Show only the first 9
    }

    // Call this function on page load
    showLatestProducts();
});

</script>
@endsection
