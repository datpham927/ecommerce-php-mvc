<?php

namespace App\Http\Controllers;

use App\Components\CategoryRecursive;
use App\Http\Requests\FormAddProductRequest;
use App\Http\Requests\FormEditProductRequest;
use App\Http\Requests\FormProductRequest;
use App\Models\admin;
use App\Models\attribute;
use App\Models\brand;
use App\Models\Category;
use App\Models\Images;
use App\Models\Product;
use App\Models\Size;
use App\Traits\AdminAuthenticationTrait;
use App\Traits\StoreImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class ProductControllers extends Controller
{
    use AdminAuthenticationTrait;
    use StoreImageTrait;
    private $category,$product,$brand,$image,$size,$attribute;
    public function __construct(Product $product, Category $category, 
    brand $brand, Images $image, Size $size,attribute $attribute){ 
        $this->category = $category;
        $this->product = $product;
        $this->brand = $brand;
        $this->image = $image;
        $this->size = $size;
        $this->attribute = $attribute;
    }
  
    public function index(){  
        $this->authenticateLogin();
       // Assuming $this->product represents the Product model in Laravel
       $products = $this->product ->where([
                    ['product_isPublished', 1],
                    ['product_isDraft', 0]
                    ]) ->latest()->paginate(5);

        return view("admin.product.index",compact('products') );
    }
    // với sự khác biệt là $this->model->where sử dụng một đối tượng model
    //  đã được khởi tạo trước đó, trong khi Model::where sử dụng tên lớp model trực tiếp.
    
    
 
     
     function getCategory($parent_id=0){
        $recursive = new CategoryRecursive($this->category);
        return $recursive->categoryRecursive($parent_id);
      }

    public function create(){ 
        $this->authenticateLogin();
        $brands= $this->brand->get();
        $htmlOptionCategory= $this->getCategory("");
        return view("admin.product.add",compact('brands',"htmlOptionCategory") );
    }
       

  

    public function store(FormAddProductRequest $request){
        try {
            $this->authenticateLogin();
            DB::beginTransaction();
        // *********    insert table Product  *********  
        $dataProduct= array();
        $dataProduct['product_name']=$request->input('product_name');
        $dataProduct['product_slug']=  Str::of($request->input('product_name'))->slug('-');
        $dataProduct['product_price']=$request->input('product_price');
        $dataProduct['product_description']=$request->input('product_description');
        $dataProduct['product_category_id']=$request->input('product_category_id');
        $dataProduct['product_brand_id']=$request->input('product_brand_id');
        $dataProduct['product_discount']=$request->input('product_discount')||0;
       
        $product_thumb=$this->handleTraitUpdateImage($request,'product_thumb',"productImages");
        if($product_thumb){
          $dataProduct['product_thumb']=$product_thumb["file_path"];
       } 
        // bản nháp  product_isDraft
         if($request->input('product_isDraft')){
          $dataProduct['product_isDraft']=true;
          $dataProduct['product_isPublished']=false;
         }
         // ------- create product ----------
         // lấy array số lượng product theo size
          $dataProductQuantities=$request->input('product_quantities');
        // ----- tính tổng số sản phẩm -------
          if ($dataProductQuantities) {
              // Use the sum method to calculate the total quantity 
           $dataProduct['product_stock']=collect($dataProductQuantities)->sum();
       }
      // tìm kiếm admin 
          $admin= admin::find(Session::get('admin_id'));
          $dataProduct['product_admin_id']=  $admin['id'];
          $newProduct =$this->product->create($dataProduct);
//*********   insert table size  *********  
          $dataSizes=$request->input('product_sizes'); //array tên kích thước 
          if($dataSizes){
                  foreach ($dataSizes as $key => $value) {
                      $this->size->create([
                          'size_name' =>$value,
                          'size_product_quantity' =>$dataProductQuantities[$key],
                          'size_product_id' =>$newProduct['id']
                      ]);
                  }
          }
// *********   insert table images *********  
         $fileImages=$request->file('product_images');
       if($fileImages){
          foreach ($fileImages as $key => $image) {
                $images=$this->HandleTraitUploadMultiple($image,'productImages'); 
                $this->image->create([
                    "image_name"=>$images['file_name'],
                    "image_url"=>$images['file_path'],
                    'image_product_id'=>$newProduct['id'],
                ]);
          }
       }
// *********   insert table attribute *********  
         $dataKeyAttributes=$request->input('product_attribute_keys');
         $dataNameAttributes=$request->input('product_attribute_names');
       if($dataKeyAttributes&&$dataNameAttributes){
          foreach ($dataKeyAttributes as $key => $keyValue) {
              $this->attribute->create([
                  "attribute_name"=>$keyValue,
                  "attribute_description"=> $dataNameAttributes[$key],
                  'attribute_product_id'=>$newProduct['id'],
              ]);
          }
       }
       DB::commit();
       return back()->with('success', 'Thêm sản phẩm thành công!');
        } catch (\Throwable $exception) {
              DB::rollBack(); //khôi phục giao dịch (không lưu)
            dd("Message".$exception->getMessage(). "line". $exception->getLine());
        }
    }

    
    public function edit($id){
        $this->authenticateLogin();
            $product=$this->product->find($id);
            $images=$this->image->where('image_product_id',$product["id"])->get();
            $attributes=$this->attribute->where('attribute_product_id',$product["id"])->get();
            $sizes= $this->size->where('size_product_id',$product["id"])->get();
            $htmlOptionCategory= $this->getCategory($product['product_category_id']);
            $brands= $this->brand->get();
           return view('admin.product.edit',compact('product','images','attributes',
                                                    'brands','htmlOptionCategory','sizes'));
    }
    public function update(FormEditProductRequest $request,$id){
        try {
            $this->authenticateLogin();
            DB::beginTransaction();
        // *********    insert table Product  *********  
        $dataProduct= array();
        $dataProduct['product_name']=$request->input('product_name');
        $dataProduct['product_slug']=  Str::of($request->input('product_name'))->slug('-');
        $dataProduct['product_price']=$request->input('product_price');
        $dataProduct['product_description']=$request->input('product_description');
        $dataProduct['product_category_id']=$request->input('product_category_id');
        $dataProduct['product_brand_id']=$request->input('product_brand_id');
        $dataProduct['product_discount']=$request->input('product_discount');
       
        if($request->hasFile("product_thumb")){
            $product_thumb=$this->handleTraitUpdateImage($request,'product_thumb',"productImages");
            if($product_thumb){
              $dataProduct['product_thumb']=$product_thumb["file_path"];
           } 
        }
        // bản nháp  product_isDraft
         if($request->has('product_isDraft')){
          $dataProduct['product_isDraft']=true;
          $dataProduct['product_isPublished']=false;
         }
         // ------- create product ----------
         
        // ----- tính tổng số sản phẩm -------
          if ($request->has('product_quantities')) {
         // lấy array số lượng product theo size
            $dataProductQuantities=$request->input('product_quantities');
              // Use the sum method to calculate the total quantity 
           $dataProduct['product_stock']=collect($dataProductQuantities)->sum();
       } 
          $this->product->find($id)->update($dataProduct);
          //
          $foundProduct=$this->product->find($id);
//*********   insert table size  *********  
          if($request->has('product_sizes')){
                $dataSizes=$request->input('product_sizes'); //array tên kích thước 
                 $this->size->where('size_product_id',$foundProduct['id'])->delete();
                  foreach ($dataSizes as $key => $value) {
                      $this->size->create([
                          'size_name' =>$value,
                          'size_product_quantity' =>$dataProductQuantities[$key],
                          'size_product_id' =>$foundProduct['id']
                      ]);
                  }
          }
// *********   insert table images *********  
if($request->has("product_images")){
           $fileImages=$request->file('product_images');
          $this->image->where('image_product_id',$foundProduct['id'])->delete();
          foreach ($fileImages as $key => $image) {
                $images=$this->HandleTraitUploadMultiple($image,'productImages'); 
              $this->image->create([
                  "image_name"=>$images['file_name'],
                  "image_url"=>$images['file_path'],
                  'image_product_id'=>$foundProduct['id'],
              ]);
          }
       }
// *********   insert table attribute *********  
       if($request->has("product_attribute_keys") and $request->has("product_attribute_names")){
        $dataKeyAttributes=$request->input('product_attribute_keys');
        $dataNameAttributes=$request->input('product_attribute_names');
        $this->attribute->where('attribute_product_id',$foundProduct['id'])->delete();
          foreach ($dataKeyAttributes as $key => $keyValue) {
              $this->attribute->create([
                  "attribute_name"=>$keyValue,
                  "attribute_description"=> $dataNameAttributes[$key],
                  'attribute_product_id'=>$foundProduct['id'],
              ]);
          }
       }
       DB::commit();
       return back()->with('success', 'Sửa sản phẩm thành công!');
        } catch (\Throwable $exception) {
              DB::rollBack(); //khôi phục giao dịch (không lưu)
            dd("Message".$exception->getMessage(). "line". $exception->getLine());
        }
    }
    public function delete($id){
        $this->authenticateLogin();
           try{
            $this->product->find($id)->delete();
            return response()->json(['code' => 200, 'message' => 'Xóa sản phẩm thành công!']);
            } catch (\Exception $e) {
                // Log lỗi
                Log::error($e->getMessage());
                // Gửi thông báo lỗi
                return response()->json(['code' => 500, 'message' => 'Đã xảy ra lỗi khi xóa sản phẩm!']);
            } 
    }
     function productDeleted(){
        $this->authenticateLogin();
          $products = $this->product::onlyTrashed()
          ->orderBy('created_at', 'desc') // Use 'created_at' or any other column you want to order by
          ->paginate(5);
        return view('admin.product.deleted',compact('products'));
        }
        function restore($id){
            try {
                // Find the soft-deleted product by ID
                $product = Product::withTrashed()->findOrFail($id);
                // Restore the product
                $product->restore();
                // Return a success response
                return response()->json(['code' => 200, 'message' => 'Khôi phục thành công!']);
            } catch (\Exception $e) {
                // Return an error response if restoration fails
                return response()->json(['code' => 500, 'message' => 'Đã xảy ra lỗi']);
            }
          }
             // ----  danh sách product nháp ---- 
    public function draftList(){  
        $this->authenticateLogin();
        // Assuming $this->product represents the Product model in Laravel
        $products = $this->product
        ->where([
            ['product_isDraft', 1],
            ['product_isPublished', 0],
        ])
        ->latest()
        ->paginate(5); 
         return view("admin.product.draft",compact('products') );
     }
     
     public function isPublish($id)
     {
         $this->authenticateLogin();
         // Find the product by its ID
         $foundProduct = $this->product->find($id);
         // Check if the product is not found
         if (!$foundProduct) {
             session()->flash('error', 'Không tìm thấy sản phẩm!');
             return back();
         }
         // Update product status
         $foundProduct->update([
             'product_isPublished' => 1,
             'product_isDraft' => 0,
         ]);
         // Flash a success message and redirect back
         session()->flash('success', 'Sản phẩm đã được đăng thành công!');
         return back();
     }
}