@extends("layout.admin")


@section("title")
<title>Thêm slider</title>
@endsection


@section("content")
<div class="content">
    @if(Session::has('success'))
    <div class="alert alert-success">
        {{ Session::get('success') }}
        @php
        Session::forget('success');
        @endphp
    </div>
    @endif
    <div class="container-fluid">
        <div class="panel-heading" style="background:none ;">
            Thêm slider
        </div>
        <div class="row">
            <div class="col-md-12">
                <form action="{{route('slider.store')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="slider_name">Tên slider</label>
                        <input type="text"  value="{{old('slider_name')}}" class="form-control @error('slider_name') is-invalid @enderror"
                            id="slider_name" name="slider_name" placeholder="Nhập tên thương hiệu" />
                        @error('slider_name')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="slider_description">Mô tả slider</label>
                        <input type="text" value="{{old('slider_description')}}" class="form-control @error('slider_description') is-invalid @enderror"
                            id="slider_description" name="slider_description" placeholder="Nhập tên thương hiệu" />
                        @error('slider_description')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="slider_image">
                            Thêm hình ảnh
                            <img src="{{asset('backend/images/image_logo2.png')}}" style='width: 30px; height: 30px;' />
                            <input type="file" class="@error('slider_image') is-invalid @enderror"
                                value="{{old('slider_image')}}" id="slider_image" style="display: none;"
                                name="slider_image" />
                        </label>
                        @error('slider_image')
                        <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Thêm</button>
                </form>

            </div>
            <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
@endsection