<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Catogory;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Variation;
use DB;
use Illuminate\Http\Request;

class ImportOpeningStockController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $productUtil
     * @return void
     */
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Display import product screen.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = auth()->user()->business_id;

        $categories = DB::select('SELECT * FROM categories WHERE business_id = ?', [$business_id]);

        // Get only the 9 latest products
        $latestProducts = Product::with('variations')
            ->where('business_id', $business_id)
            ->where('not_for_selling', 1)
            ->latest()  // Order by the latest products
            ->take(9)   // Limit to 9 products
            ->get();

        // Get all products for category filter
        $products = Product::with('variations')
            ->where('business_id', $business_id)
            ->where('not_for_selling',1)
            ->get();

        $date_formats = Business::date_formats();
        $date_format = session('business.date_format');
        $date_format = isset($date_formats[$date_format]) ? $date_formats[$date_format] : $date_format;

        return view('import_opening_stock.index')
            ->with(compact('date_format', 'latestProducts', 'products', 'categories'));
    }


    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            $notAllowed = $this->productUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }
    
            // Set maximum PHP execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
    
            if ($request->has('products')) 
            {
                $products = $request->input('products');
    
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
    
                $is_valid = true;
                $error_msg = '';
    
                DB::beginTransaction();
    
                foreach ($products as $key => $value) {
                    $row_no = $key + 1;
    
                    // Log the entire row data for debugging
                    \Log::info("Processing row no. $row_no: " . json_encode($value));
    
                    // Validate product name
                    if (! empty($value['name'])) {
                        $product_name = $value['name'];
                        $product_info = DB::table('products')
                            ->join('variations as v', 'products.id', '=', 'v.product_id')
                            ->select('products.id', 'v.id as variation_id', 'products.enable_stock')
                            ->where('products.name', $product_name)
                            ->where('products.business_id', $business_id)
                            ->first();
    
                        if (empty($product_info)) {
                            $is_valid = false;
                            $error_msg = "Product with name $product_name not found in row no. $row_no";
                            break;
                        } elseif ($product_info->enable_stock == 0) {
                            $is_valid = false;
                            $error_msg = "Manage Stock not enabled for the product with name $product_name in row no. $row_no";
                            break;
                        }
                    } else {
                        $is_valid = false;
                        $error_msg = "PRODUCT NAME is required in row no. $row_no";
                        break;
                    }
    
                    // Validate location details
                    if (isset($value['location']) && !empty(trim($value['location']))) {
                        $location_name = trim($value['location']);
                        $location = BusinessLocation::where('name', $location_name)
                            ->where('business_id', $business_id)
                            ->first();
                        if (empty($location)) {
                            $is_valid = false;
                            $error_msg = "Location with name '$location_name' not found in row no. $row_no";
                            break;
                        }
                    } else {
                        $location = BusinessLocation::where('business_id', $business_id)->first();
                    }
    
                    // Validate and prepare opening stock details
                    $opening_stock = [
                        'quantity' => trim($value['quantity']),
                        'location_id' => $location->id,
                        'lot_number' => isset($value['lot_number']) ? trim($value['lot_number']) : null,
                        'exp_date' => isset($value['exp_date']) ? trim($value['exp_date']) : null,
                    ];

                    $default_sell_price = $value['unit_cost'];
    
                    // Log the fetched default_sell_price for debugging
                    \Log::info("Row no. $row_no: Fetched default_sell_price: " . $default_sell_price);
    
                    // Validate default sell price
                    if (! empty(trim($default_sell_price)) && is_numeric(trim($default_sell_price))) {
                        $default_sell_price = trim($default_sell_price);
                    } else {
                        $is_valid = false;
                        $error_msg = "Invalid DEFAULT SELL PRICE in row no. $row_no. Value: " . $default_sell_price;
                        break;
                    }
    
                    // Validate quantity
                    if (! is_numeric(trim($value['quantity']))) {
                        $is_valid = false;
                        $error_msg = "Invalid quantity " . $value['quantity'] . " in row no. $row_no";
                        break;
                    }
    
                    // Check for existing transaction
                    $os_transaction = Transaction::where('business_id', $business_id)
                        ->where('location_id', $location->id)
                        ->where('type', 'opening_stock')
                        ->where('opening_stock_product_id', $product_info->id)
                        ->first();
    
                    // Add opening stock
                    $this->addOpeningStock($opening_stock, $product_info, $business_id, $default_sell_price, $os_transaction);
                }
    
                if (! $is_valid) {
                    throw new \Exception($error_msg);
                }
    
                $output = ['success' => 1, 'msg' => __('product.file_imported_successfully')];
                DB::commit();
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
    
            $output = ['success' => 0, 'msg' => 'Message:' . $e->getMessage()];
            return redirect('import-opening-stock')->with('notification', $output);
        }
    
        return redirect('import-opening-stock')->with('status', $output);
    }
    


    /**
     * Adds opening stock of a single product
     *
     * @param  array  $opening_stock
     * @param  obj  $product
     * @param  int  $business_id
     * @param  float $default_sell_price
     * @param  obj  $transaction (optional)
     * @return void
     */
    private function addOpeningStock($opening_stock, $product, $business_id, $default_sell_price, $transaction = null)
    {
        $user_id = request()->session()->get('user.id');

        $transaction_date = request()->session()->get('financial_year.start');
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

        // Total before transaction tax (no tax considered)
        $total_before_trans_tax = $opening_stock['quantity'] * $default_sell_price;

        // Add opening stock transaction
        if (empty($transaction)) {
            $transaction = new Transaction();
            $transaction->type = 'opening_stock';
            $transaction->status = 'received';
            $transaction->opening_stock_product_id = $product->id;
            $transaction->business_id = $business_id;
            $transaction->transaction_date = $transaction_date;
            $transaction->location_id = $opening_stock['location_id'];
            $transaction->payment_status = 'paid';
            $transaction->created_by = $user_id;
            $transaction->total_before_tax = 0;
            $transaction->final_total = 0;
        }
        $transaction->total_before_tax += $total_before_trans_tax;
        $transaction->final_total += $total_before_trans_tax;
        $transaction->save();

        // Create purchase line
        $transaction->purchase_lines()->create([
            'product_id' => $product->id,
            'variation_id' => $product->variation_id,
            'quantity' => $opening_stock['quantity'],
            'pp_without_discount' => $default_sell_price,
            'purchase_price' => $default_sell_price,
            'purchase_price_inc_tax' => $default_sell_price, // No tax considered
            'exp_date' => ! empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null,
            'lot_number' => ! empty($opening_stock['lot_number']) ? $opening_stock['lot_number'] : null,
        ]);

        // Update variation location details
        $this->productUtil->updateProductQuantity($opening_stock['location_id'], $product->id, $product->variation_id, $opening_stock['quantity']);

        // Update default_sell_price in the variations table
        DB::table('variations')
            ->where('id', $product->variation_id)
            ->update(['sell_price_inc_tax' => $default_sell_price,
            'default_sell_price' => $default_sell_price,
            'dpp_inc_tax' => $default_sell_price,
            'default_purchase_price'=>$default_sell_price 
        ]);
    }
}
