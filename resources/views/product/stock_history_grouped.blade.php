@extends('layouts.app')
@section('title', __('stock_adjustment.stock_adjustments'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('stock_adjustment.stock_adjustments')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('stock_adjustment.opening_stock_by_date')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product_stocks as $date => $stocks)
                        <tr>
                            <td>{{ $date }}</td>
                            <td>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#stockModal" data-date="{{ $date }}">
                                    @lang('messages.view')
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endcomponent

    <!-- Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalLabel">@lang('messages.product_stock_details')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('messages.product_name')</th>
                                <th>@lang('messages.variation_name')</th>
                                <th>@lang('messages.stock')</th>
                            </tr>
                        </thead>
                        <tbody id="stock-details-body">
                            <!-- Stock details will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
@stop

@section('javascript')
<script>
    $('#stockModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var date = button.data('date'); // Extract info from data-* attributes

        var modal = $(this);
        modal.find('.modal-title').text('@lang('messages.product_stock_details') - ' + date);

        // Clear previous details
        $('#stock-details-body').empty();

        // Fetch the stocks for the particular date and populate the modal
        var stocks = {!! json_encode($product_stocks) !!};
        if (stocks[date]) {
            stocks[date].forEach(function (stock) {
                $('#stock-details-body').append(
                    '<tr>' +
                    '<td>' + stock.product_name + '</td>' +
                    '<td>' + stock.variation_name + '</td>' +
                    '<td>' + stock.stock + '</td>' +
                    '</tr>'
                );
            });
        }
    });
</script>
@endsection
