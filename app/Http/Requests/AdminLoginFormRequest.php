<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_name' => 'required|unique:admins',
            'user_address' => 'required',
            'user_mobile' => 'required|numeric',
            'user_cmnd' => 'required',
            'user_password' => 'required|min:6',
            'password_confirm' => 'required|same:user_password',
            'user_image_url' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'user_roles' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'user_name.required' => 'Vui lòng nhập tên đăng nhập.',
            'user_name.unique' => 'Tên đăng nhập đã tồn tại trong hệ thống.',
            'user_address.required' => 'Vui lòng nhập địa chỉ.',
            'user_cmnd.required' => 'Vui lòng nhập chứng minh nhân dân.',
            'user_roles.required' => 'Vui lòng chọn vai trò.',
            'user_mobile.required' => 'Vui lòng nhập số điện thoại.',
            'user_mobile.numeric' => 'Số điện thoại phải là số.',
            'user_password.required' => 'Vui lòng nhập mật khẩu.',
            'user_password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password_confirm.required' => 'Vui lòng xác nhận mật khẩu.',
            'password_confirm.same' => 'Mật khẩu xác nhận không khớp.',
            'user_image_url.required' => 'Vui lòng chọn ảnh đại diện.',
            'user_image_url.image' => 'Tệp được chọn không phải là hình ảnh.',
            'user_image_url.mimes' => 'Định dạng hình ảnh không hợp lệ.',
            'user_image_url.max' => 'Kích thước ảnh không được lớn hơn :max KB.',
        ];
    }
}
