<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Products;
use App\Models\Categories;

class MainController extends Controller
{
    public function index () {
        return view('welcome');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeCategories (Request $request, Response $response) {
        // init model
        $categories = new Categories;

        // get csv limit 100 rows
        $data = array_map('str_getcsv', file($request->file('file_csv')->getRealPath()));
        $csv_data = array_slice($data, 1, 100);

        // insert model categories to db
        for ($i=0; $i < count($csv_data); $i++) { 
            $data_index = $csv_data[$i];

            if(count($categories->get()) == 0) { 
                // condition if this first attemp model
                $categories->insert(
                    [
                        'code' => $data_index[12] . '00' . 1,
                        'name' => $data_index[13],
                        'note' => $data_index[14],
                    ]
                );
            } else {
                // condition if have same name
                $odd_categories = array_filter(
                    $categories->get()->toArray(),
                    fn ($category) => $category['name'] === $data_index[13]
                );

                if($odd_categories == null) {
                    $categories->insert(
                        [
                            'code' => $data_index[12] . '00' . $categories->all('id')->last()['id'] + 1,
                            'name' => $data_index[13],
                            'note' => $data_index[14],
                        ]
                    );
                }
            }
        }

        return response('success', 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeProducts (Request $request) {
        // init model
        $categories = new Categories;
        $products = new Products;

        // check if have categories field row
        if(count($categories->get()) == 0) {
            return response('categories empty', 400);
        } else {
            // get csv limit 100 rows
            $data = array_map('str_getcsv', file($request->file('file_csv')->getRealPath()));
            $csv_data = array_slice($data, 1, 100);
    
            // insert model categories to db
            for ($i=0; $i < count($csv_data); $i++) { 
                $data_index = $csv_data[$i];
    
                // get eloquent category by name
                $name_category = $categories->where('name', $data_index[13])->first();
    
                $code = count($products->get()) != 0 ? $name_category['code'] . '00' . 1 : $categories->all('id')->last()['id'] + 1;
    
                $name_category->product()->create(
                    [
                        'product_type_id' => $data_index[16],
                        'code' => $code,
                        'name' => $data_index[15],
                        'barcode' => $code . '00' . $name_category[0] . $data_index[16],
                        'note' => $data_index[14]
                    ]
                );
            }
    
            return response('success', 200);
        }
    }
}
