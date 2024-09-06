@extends('layouts.app')
@section('title', __('lang_v1.product_stock_history'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.product_stock_history')</h1>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.widget', ['title' => $product->name])
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('product_id',  __('sale.product') . ':') !!}
                {!! Form::select('product_id', [$product->id=>$product->name . ' - ' . $product->sku], $product->id, ['class' => 'form-control', 'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, request()->input('location_id', null), ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('location_id',  __('Date Range') . ':') !!}<br>
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="stock_adjustment_date_filter">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
            </div>
        </div>
        @if($product->type == 'variable')
            <div class="col-md-3">
                <div class="form-group">
                    <label for="variation_id">@lang('product.variations'):</label>
                    <select class="select2 form-control" name="variation_id" id="variation_id">
                        @foreach($product->variations as $variation)
                            <option value="{{$variation->id}}"
                            @if(request()->input('variation_id', null) == $variation->id)
                                selected
                            @endif
                            >{{$variation->product_variation->name}} - {{$variation->name}} ({{$variation->sub_sku}})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" id="variation_id" name="variation_id" value="{{$product->variations->first()->id}}">
        @endif
    @endcomponent
    @component('components.widget')
        <div id="product_stock_history" style="display: none;"></div>
    @endcomponent
    </div>
</div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
   <script type="text/javascript">
        $(document).ready(function() {
    // Initialize the date range picker
    $('#stock_adjustment_date_filter').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#stock_adjustment_date_filter span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            load_stock_history($('#variation_id').val(), $('#location_id').val());
        }
    );

    // Initialize select2 for product selection
    $('#product_id').select2({
        ajax: {
            url: '/products/list-no-variation',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term, // search term
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        minimumInputLength: 1,
        escapeMarkup: function(m) {
            return m;
        },
    }).on('select2:select', function(e) {
        var data = e.params.data;
        window.location.href = "{{url('/')}}/products/stock-history/" + data.id;
    });

    // Load stock history on page load
    load_stock_history($('#variation_id').val(), $('#location_id').val());

    // Load stock history when variation or location is changed
    $(document).on('change', '#variation_id, #location_id', function() {
        load_stock_history($('#variation_id').val(), $('#location_id').val());
    });
});

function load_stock_history(variation_id, location_id) {
    $('#product_stock_history').fadeOut();

    // Get date range filter values
    var start_date = $('#stock_adjustment_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var end_date = $('#stock_adjustment_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');

    $.ajax({
        url: '/products/stock-history/' + variation_id,
        data: {
            location_id: location_id,
            start_date: start_date,
            end_date: end_date
        },
        dataType: 'html',
        success: function(result) {
            $('#product_stock_history')
                .html(result)
                .fadeIn();

            __currency_convert_recursively($('#product_stock_history'));

            $('#stock_history_table').DataTable({
                searching: false,
                ordering: false
            });
        },
    });
}
   </script>
@endsection
