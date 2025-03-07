@if(!session('business.enable_price_tax')) 
  @php
    $default = 0;
    $class = 'hide';
  @endphp
@else
  @php
    $default = null;
    $class = '';
  @endphp
@endif
@php
    $common_settings = session()->get('business.common_settings');
@endphp

<div class="col-sm-12"><br>
    <div class="table-responsive">
    <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
        <tr>
          <th>@lang('product.default_purchase_price')</th>
          <th>@lang('product.profit_percent') @show_tooltip(__('tooltip.profit_percent'))</th>
          <th>@lang('product.default_selling_price')</th>
        </tr>
        @foreach($product_deatails->variations as $variation )
            @php
                $is_image_required = !empty($common_settings['is_product_image_required']) && count($variation->media) == 0;
            @endphp
            @if($loop->first)
                <tr>
                    <td>
                        <input type="hidden" name="single_variation_id" value="{{$variation->id}}">

                        <div class="col-sm-12">
                          {!! Form::label('single_dpp', trans('Purchase Price') . ':*') !!}

                          {!! Form::text('single_dpp', @num_format($variation->default_purchase_price), ['class' => 'form-control input-sm dpp input_number', 'placeholder' => __('product.exc_of_tax'), 'required']); !!}
                        </div>

                        <div class="col-sm-6" style="display: none">
                          {!! Form::label('single_dpp_inc_tax', trans('product.inc_of_tax') . ':*') !!}
                        
                          {!! Form::text('single_dpp_inc_tax', @num_format($variation->dpp_inc_tax), ['class' => 'form-control input-sm dpp_inc_tax input_number', 'placeholder' => __('product.inc_of_tax'), 'required']); !!}
                        </div>
                    </td>

                    <td>
                      {!! Form::label('single_dpp', trans('Profit Margin') . ':*') !!}
                        {!! Form::text('profit_percent', @num_format($variation->profit_percent), ['class' => 'form-control input-sm input_number', 'id' => 'profit_percent', 'required']); !!}
                    </td>

                    <td>
                      {!! Form::label('single_dpp', trans('Selling Price') . ':*') !!}
                        {!! Form::text('single_dsp', @num_format($variation->default_sell_price), ['class' => 'form-control input-sm dsp input_number', 'placeholder' => __('product.exc_of_tax'), 'id' => 'single_dsp', 'required']); !!}

                        {!! Form::text('single_dsp_inc_tax', @num_format($variation->sell_price_inc_tax), ['class' => 'form-control input-sm hide input_number', 'placeholder' => __('product.inc_of_tax'), 'id' => 'single_dsp_inc_tax', 'required']); !!}
                    </td>
                    
                </tr>
            @endif
        @endforeach
    </table>
    </div>
</div>