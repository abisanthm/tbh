@if(empty($only) || in_array('sell_list_filter_location_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

        {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_payment_status', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_payment_status',  __('purchase.payment_status') . ':') !!}
        {!! Form::select('sell_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_date_range', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
    </div>
</div>
@endif
@if(!empty($shipping_statuses))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':') !!}
            {!! Form::select('shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
        </div>
    </div>
@endif
