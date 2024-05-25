<?php

namespace App\Http\Controllers\admin; 
use App\Http\Controllers\Controller;
use App\Http\Requests\FormSliderRequest;
use App\Models\slider;
use App\Models\product;
use App\Models\User;
use App\Traits\AdminAuthenticationTrait;
use App\Traits\StoreImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SliderControllers extends Controller
{
   
    use StoreImageTrait;
    private $slider,$product;
    
    public function __construct(slider $slider,product $product){ 
        $this->slider = $slider;
        $this->product = $product;
    }
    
    public function index(){  
        
        $sliders=$this->slider->latest()->paginate(5);
        return view("admin.slider.index",compact("sliders"));
    }
    // với sự khác biệt là $this->model->where sử dụng một đối tượng model
    //  đã được khởi tạo trước đó, trong khi Model::where sử dụng tên lớp model trực tiếp.
   

    public function create(){  
          return view("admin.slider.add",);
    }
    
    public function store(FormSliderRequest  $request, slider $slider)
    {
        
        $sliderName = $request->input("slider_name");
        $slug = Str::of($sliderName)->slug('-');
        // Tạo một mảng chứa dữ liệu slider
        $sliderData = [
            'slider_name' => $sliderName,
            'slider_description' => $request->input("slider_description"),
        ];
        $slider_image=$this->handleTraitUpdateImage($request,'slider_image',"slider");
        if($slider_image){
          $sliderData['slider_image']=$slider_image["file_path"];
       } 
        $this->slider->create($sliderData);
        // Gửi thông báo thành công
        return back()->with('success', 'Thêm slider thành công!');
        
    }
    


    
    public function edit($id){
        
        $slider=$this->slider::find($id);  
        return view("admin.slider.edit",compact("slider"));
    }
    public function update(FormSliderRequest $request,$id){
        
            // tìm slider
            $slider=$this->slider::find($id); 
            $sliderName = $request->input("slider_name");
            // Tạo một mảng chứa dữ liệu slider
            $sliderData = [
                'slider_name' => $sliderName,
                'slider_description' => $request->input("slider_description"),
            ];
            $slider_image=$this->handleTraitUpdateImage($request,'slider_image',"slider");
              $sliderData['slider_image']=$slider_image["file_path"];
            // update slider 
            $slider->update($sliderData);

            session()->flash('success', 'Cập nhật slider thành công!');
            return redirect()->route('slider.index'); 
    }
    public function delete($id)
{
    try {
        // Tìm slider bằng id
        $slider = $this->slider->find($id);
        // Kiểm tra nếu slider không tồn tại
        if (!$slider) {
            return response()->json(['code' => 404, 'message' => 'Slider không tồn tại']);
        }
        // Kiểm tra và xóa tệp hình ảnh nếu tồn tại
        if (file_exists(public_path($slider->slider_image))) {
            unlink(public_path($slider->slider_image));
        }
        // Xóa slider khỏi cơ sở dữ liệu
        $slider->delete();

        // Trả về phản hồi thành công
        return response()->json(['code' => 200, 'message' => 'Xóa slider thành công!']);
    } catch (\Exception $e) {
        // Ghi log lỗi
        Log::error($e->getMessage());

        // Trả về phản hồi lỗi
        return response()->json(['code' => 500, 'message' => 'Đã xảy ra lỗi']);
    }
}

}