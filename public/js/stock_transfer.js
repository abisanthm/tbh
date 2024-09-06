$(document).ready(function () {
    // Initialize autocomplete for adding products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function (event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function (event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function (event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);

                        // Prompt for quantity input
                        swal({
                            text: 'Enter quantity for ' + ui.item.name,
                            content: {
                                element: 'input',
                                attributes: {
                                    type: 'number',
                                    min: 1,
                                    value: 1,
                                    className: 'form-control',
                                },
                            },
                            buttons: {
                                cancel: true,
                                confirm: true,
                            },
                        }).then(function (quantity) {
                            if (quantity !== null && quantity > 0) {
                                stock_transfer_product_row(ui.item.variation_id, quantity);
                            } else {
                                swal('Invalid quantity');
                            }
                        });
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>').append(string).appendTo(ul);
            }
        };
    }

    // Handle location change
    $('select#location_id').change(function () {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });

    // Update row total when quantity or unit price changes
    $(document).on('change', 'input.product_quantity', function () {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function () {
        update_table_row($(this).closest('tr'));
    });

    // Remove product row
    $(document).on('click', '.remove_product_row', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $(this).closest('tr').remove();
                update_table_total();
            }
        });
    });

    // Date picker initialization
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    jQuery.validator.addMethod(
        'notEqual',
        function (value, element, param) {
            return this.optional(element) || value != param;
        },
        'Please select different location'
    );

    $('form#stock_transfer_form').validate({
        rules: {
            transfer_location_id: {
                notEqual: function () {
                    return $('select#location_id').val();
                },
            },
        },
    });

    // Save stock transfer form
    $('#save_stock_transfer').click(function (e) {
        e.preventDefault();
        if ($('table#stock_adjustment_product_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }
        if ($('form#stock_transfer_form').valid()) {
            $('form#stock_transfer_form').submit();
        } else {
            return false;
        }
    });

    // Initialize stock transfer table
    stock_transfer_table = $('#stock_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: false,
        aaSorting: [[0, 'desc']],
        ajax: '/stock-transfers',
        columnDefs: [
            {
                targets: 8,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_from', name: 'l1.name' },
            { data: 'location_to', name: 'l2.name' },
            { data: 'status', name: 'status' },
            { data: 'shipping_charges', name: 'shipping_charges' },
            { data: 'final_total', name: 'final_total' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#stock_transfer_table'));
        },
    });

    var detailRows = [];

    // Toggle stock transfer details
    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function () {
        var tr = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            row.child.hide();
            detailRows.splice(idx, 1);
        } else {
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            row.child(get_stock_transfer_details(row.data())).show();
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    stock_transfer_table.on('draw', function () {
        $.each(detailRows, function (i, id) {
            $('#' + id + ' .view_stock_transfer').trigger('click');
        });
    });

    // Delete Stock Transfer
    $(document).on('click', 'button.delete_stock_transfer', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_transfer_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

// Function to add product row with a specified quantity
function stock_transfer_product_row(variation_id, quantity) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();

    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: {
            row_index: row_index,
            variation_id: variation_id,
            location_id: location_id,
            quantity: quantity, // Pass the quantity to the server
            type: 'stock_transfer',
        },
        dataType: 'html',
        success: function (result) {
            var newRow = $(result);

            // Ensure the quantity is set correctly in the row's quantity input field
            newRow.find('.product_quantity').val(quantity);

            // Calculate the row's total based on the quantity and unit price
            update_row_total(newRow);

            // Append the new row to the table
            $('table#stock_adjustment_product_table tbody').append(newRow);

            // Update the table total
            update_table_total();

            // Increment the product row index
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_row_total(row) {
    var quantity = parseFloat(row.find('.product_quantity').val());
    var unit_price = parseFloat(row.find('.product_unit_price').val());

    if (!isNaN(quantity) && !isNaN(unit_price)) {
        var total = quantity * unit_price;
        row.find('.product_line_total').val(total.toFixed(2));
    }
}

function update_table_total() {
    var total = 0;

    // Iterate through each row to sum the total
    $('table#stock_adjustment_product_table tbody tr').each(function () {
        var row_total = parseFloat($(this).find('.product_line_total').val());

        if (!isNaN(row_total)) {
            total += row_total;
        }
    });

    // Update the total in the UI
    $('#total_adjustment').text(total.toFixed(2));
    $('#final_total_text').text(total.toFixed(2));
    $('#total_amount').val(total.toFixed(2)); // Set the hidden total field
}

$(document).on('change', '#shipping_charges', function () {
    update_table_total();
});

$(document).on('change', 'select.sub_unit', function () {
    var tr = $(this).closest('tr');
    var selected_option = $(this).find(':selected');
    var multiplier = parseFloat(selected_option.data('multiplier'));
    var allow_decimal = parseInt(selected_option.data('allow_decimal'));
    tr.find('input.base_unit_multiplier').val(multiplier);

    var base_unit_price = tr.find('input.hidden_base_unit_price').val();

    var unit_price = base_unit_price * multiplier;
    var unit_price_element = tr.find('input.product_unit_price');
    __write_number(unit_price_element, unit_price);

    var qty_element = tr.find('input.product_quantity');
    var base_max_avlbl = qty_element.data('qty_available');
    var error_msg_line = 'pos_max_qty_error';

    if (tr.find('select.lot_number').length > 0) {
        var lot_select = tr.find('select.lot_number');
        if (lot_select.val()) {
            base_max_avlbl = lot_select.find(':selected').data('qty_available');
            error_msg_line = 'lot_max_qty_error';
        }
    }
    qty_element.attr('data-decimal', allow_decimal);
    var abs_digit = true;
    if (allow_decimal) {
        abs_digit = false;
    }
    qty_element.rules('add', {
        abs_digit: abs_digit,
    });

    if (base_max_avlbl) {
        var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
        var formated_max_avlbl = __number_f(max_avlbl);
        var unit_name = selected_option.data('unit_name');
        var max_err_msg = __translate(error_msg_line, {
            max_val: formated_max_avlbl,
            unit_name: unit_name,
        });
        qty_element.attr('data-rule-max-value', max_avlbl);
        qty_element.attr('data-msg-max-value', max_err_msg);
        qty_element.rules('add', {
            'max-value': max_avlbl,
            messages: {
                'max-value': max_err_msg,
            },
        });
        qty_element.trigger('change');
    }
    qty_element.valid();
    update_table_row($(this).closest('tr'));
});

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var multiplier = 1;

    if (tr.find('select.sub_unit').length) {
        multiplier = parseFloat(tr.find('select.sub_unit').find(':selected').data('multiplier'));
    }
    quantity = quantity * multiplier;

    var unit_price = parseFloat(tr.find('input.hidden_base_unit_price').val());
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    var div = $('<div/>').addClass('loading').text('Loading...');
    $.ajax({
        url: '/stock-transfers/' + rowData.DT_RowId,
        dataType: 'html',
        success: function (data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

$(document).on('click', 'a.stock_transfer_status', function (e) {
    e.preventDefault();
    var href = $(this).data('href');
    var status = $(this).data('status');
    $('#update_stock_transfer_status_modal').modal('show');
    $('#update_stock_transfer_status_form').attr('action', href);
    $('#update_stock_transfer_status_form #update_status').val(status);
    $('#update_stock_transfer_status_form #update_status').trigger('change');
});

$(document).on('submit', '#update_stock_transfer_status_form', function (e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'post',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function (xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function (result) {
            if (result.success == true) {
                $('div#update_stock_transfer_status_modal').modal('hide');
                toastr.success(result.msg);
                stock_transfer_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
            $('#update_stock_transfer_status_form')
                .find('button[type="submit"]')
                .attr('disabled', false);
        },
    });
});
$(document).on('shown.bs.modal', '.view_modal', function () {
    __currency_convert_recursively($('.view_modal'));
});
