@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3>Product Stock History</h3>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>Transaction Type</th>
            <th>Quantity Change</th>
            <th>Stock</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stock_history as $history)
            <tr>
                <td>{{ $history['date'] }}</td>
                <td>{{ $history['type_label'] }}</td>
                <td>{{ $history['quantity_change'] }}</td>
                <td>{{ $history['stock'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
</div>
@endsection