@extends('layouts.app')
@section('title', __('lang_v1.edit_stock_transfer'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.edit_stock_transfer')</h1>
</section>

<!-- Main content -->
<section class="content no-print">
	{!! Form::open(['url' => action([\App\Http\Controllers\StockTransferController::class, 'update'], [$sell_transfer->id]), 'method' => 'put', 'id' => 'stock_transfer_form' ]) !!}

		@component('components.widget', ['class' => 'box-solid'])
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime($sell_transfer->transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', $sell_transfer->ref_no, ['class' => 'form-control', 'readonly']); !!}
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('status', __('sale.status').':*') !!} @show_tooltip(__('lang_v1.completed_status_help'))
						{!! Form::select('status', $statuses, $sell_transfer->status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'status']); !!}
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-sm-6">
					<div class="form-group">
						{!! Form::label('location_id', __('lang_v1.location_from').':*') !!}
						{!! Form::select('location_id', $business_locations, $sell_transfer->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'location_id', 'disabled']); !!}
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						{!! Form::label('transfer_location_id', __('lang_v1.location_to').':*') !!}
						{!! Form::select('transfer_location_id', $business_locations, $purchase_transfer->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'id' => 'transfer_location_id', 'disabled']); !!}
					</div>
				</div>
				
			</div>
		@endcomponent
	
		@component('components.widget', ['class' => 'box-solid'])
		<div class="box-header">
        	<h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
       	</div>
		<div class="">
			<div class="row">
				<div class="col-sm-8 col-sm-offset-2">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-search"></i>
							</span>
							{!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_srock_adjustment', 'placeholder' => __('stock_adjustment.search_product')]); !!}
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="table-responsive">
					<table class="table table-bordered table-striped table-condensed" 
					id="stock_adjustment_product_table">
						<thead>
							<tr>
								<th class="col-sm-4 text-center">	
									@lang('sale.product')
								</th>
								<th class="col-sm-2 text-center">
									@lang('sale.qty')
								</th>
								<th class="col-sm-2 text-center show_price_with_permission">
									@lang('sale.unit_price')
								</th>
								<th class="col-sm-2 text-center show_price_with_permission">
									@lang('sale.subtotal')
								</th>
								<th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody>
							@php
								$product_row_index = 0;
								$subtotal = 0;
							@endphp
							@foreach($products as $product)
								@include('stock_transfer.partials.product_table_row', ['product' => $product, 'row_index' => $loop->index, 'sub_units' => !empty($product->unit_details) ? $product->unit_details : []])
								@php
									$product_row_index = $loop->index + 1;
									$subtotal += ($product->quantity_ordered*$product->last_purchased_price);
								@endphp
							@endforeach
						</tbody>
						<tfoot>
							<tr class="text-center show_price_with_permission"><td colspan="3"></td><td><div class="pull-right"><b>@lang('sale.total'):</b> <span id="total_adjustment">{{@num_format($subtotal)}}</span></div></td></tr>
						</tfoot>
					</table>
					<input type="hidden" id="product_row_index" value="{{$product_row_index}}">
					</div>
				</div>
			</div>
		</div>
	@endcomponent
	@component('components.widget', ['class' => 'box-solid'])
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
							{!! Form::label('shipping_charges', __('lang_v1.shipping_charges') . ':') !!}
							{!! Form::text('shipping_charges', @num_format($sell_transfer->shipping_charges), ['class' => 'form-control input_number', 'placeholder' => __('lang_v1.shipping_charges')]); !!}
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('additional_notes',__('purchase.additional_notes')) !!}
						{!! Form::textarea('additional_notes', $sell_transfer->additional_notes, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
				</div>
			</div>
			@php
				$final_total = $subtotal + $sell_transfer->shipping_charges;
			@endphp
			<div class="row">
				<div class="col-md-12 text-right show_price_with_permission">
					<input type="hidden" id="total_amount" name="final_total" value="{{$sell_transfer->final_total}}">
					<b>@lang('stock_adjustment.total_amount'):</b> <span id="final_total_text">{{@num_format($final_total)}}</span>
				</div>
				<br>
				<br>
				<div class="col-sm-12 text-center">
					<button type="submit" id="save_stock_transfer" class="btn btn-primary btn-big">@lang('messages.save')</button>
				</div>
			</div>
		@endcomponent
<!--box end-->
	{!! Form::close() !!}
	
	<!-- Modal -->
	<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="productModalLabel">Select Product</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<!-- Category Filter -->
					<div class="form-group">
						<label for="category_filter">Filter by Category:</label>
						<select id="category_filter" class="form-control">
							<option value="all">All</option>
							@foreach($categories as $category)
								<option value="{{ $category->id }}">{{ $category->name }}</option>
							@endforeach
						</select>
					</div>

					<ul id="product-list"></ul>  
				</div>
			</div>
		</div>
	</div>
</section>
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		__page_leave_confirmation('#stock_transfer_form');
	</script>
	<script>
        $(document).ready(function() {
            // Open modal when search input is clicked
            $('#search_product_for_srock_adjustment').click(function() {
                $('#productModal').modal('show');
            });

            // Handle product selection from the modal
            $(document).on('click', '.product-item', function() {
                var productName = $(this).data('name');
                $('#search_product_for_srock_adjustment').val(productName);
                $('#productModal').modal('hide');
                $('#search_product_for_srock_adjustment').trigger('input');
            });

            $('#save_stock_transfer').click(function(e) {
                e.preventDefault(); // Prevent the default form submission

                // Assuming you have a validation function or use a validation plugin
                if ($('#stock_transfer_form').valid()) {
                    $('#stock_transfer_form').submit(); // Manually trigger the form submission
                } else {
                    toastr.error('Please correct the errors in the form before submitting.');
                }
            });
    
            $('#search_product_for_srock_adjustment').on('input', function() {
                var searchQuery = $(this).val();
                console.log('Product search triggered with value:', searchQuery);
                // Example: You might make an AJAX call here to search or filter products
            });
    });
    </script>
    <script>
        $(document).ready(function() {
    // Function to handle location_id change or initial load
    function handleLocationChange() {
        var location_id = $('#location_id').val(); // Get the selected location_id value
        var category_id = $('#category_filter').val(); // Get the selected category_id value

        if (location_id) {
            console.log("Selected Location ID: " + location_id);

            $.ajax({
                url: '{{ route('products.selist') }}',  // Adjust the route as necessary
                type: 'GET',
                data: {
                    location_id: location_id,
                    category_id: category_id,  // Pass the category ID for filtering
                    term: ''  // Optional: You can send a search term or leave it empty
                },
                success: function(response) {
                    var productList = $('#product-list');
                    productList.empty();  // Clear the current list

                    // Loop through the returned product details and append them to the list
                    $.each(response, function(index, product) {
                        productList.append(`
                            <li class="btn btn-sm btn-primary product-item" data-name="${product.name}">
                                ${product.name} - ${product.total_stock}
                            </li>
                        `);
                    });
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        } else {
            $('#product-list').empty();  // Clear the list if no location is selected
        }
    }

    // Listen to changes on the location_id dropdown
    $('#location_id').change(handleLocationChange);

	// Listen to changes on the category_filter dropdown
    $('#category_filter').change(handleLocationChange);

    // Trigger the function on page load if location_id is already set
    handleLocationChange();
});
        </script>
@endsection
@cannot('view_purchase_price')
    <style>
        .show_price_with_permission {
            display: none !important;
        }
    </style>
@endcannot