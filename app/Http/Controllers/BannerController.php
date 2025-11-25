<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\BannerAction;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    protected $banner;

    public function __construct(Banner $banner, BannerAction $bannerAction) {

        $this->banner = $banner;
        $this->bannerAction = $bannerAction;
    }

    public function index(Request $request) 
    {   
        $banners = $this->banner->with('bannerAction')->get();

        return view('setting.banner.index', compact('banners'));          
    }

    public function create(Request $request) 
    {
        return view('setting.banner.create');          
    }

    public function store(Request $request) {
        $imageUrl = $this->ImageUpload($request);
        
        $targets = $request->targets ?? null;
        $links = $request->links ?? null; 

        $bannerData  =  $this->banner->create([
            'page_name'             => $request->page_name ?? null,
            'page_tittle'           => $request->page_tittle,
            'image'                 => $imageUrl,
            'description'           => $request->description,
            'additional_info'       => $request->additional_info,
        ]);

        foreach ($targets as $index => $target) {
            if (!empty($target) && !empty($links[$index])) {
                $this->bannerAction->Create(
                    [
                        'banner_id' => $bannerData->id,
                        'target' => $target,
                        'link' => $links[$index]
                    ]
                );
            }
        }

        return redirect()->route('banner.index')->with('success', 'Banner created successfully.');
    }

    public function edit($id)
    {
        $banner = $this->banner->with('bannerAction')->findOrFail($id);
        return view('setting.banner.create', compact('banner'));
    }

    public function update(Request $request, $id) {
        $bannerData  = $this->banner->with('bannerAction')->findOrFail($id);

        $imageUrl   = $this->ImageUpload($request) ?? $bannerData->image;

        $bannerData->update([
                'page_name'             => $request->page_name,
                'page_tittle'           => $request->page_tittle,
                'image'                 => $imageUrl,
                'description'           => $request->description,
                'additional_info'       => $request->additional_info,
        ]);

        return redirect()->route('banner.index')->with('success', 'Banner updated successfully.');
    }

    protected function ImageUpload(Request $request)
    {
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('bannerImages', 'public');
            return Storage::url($imagePath);
        }

        return null;
    }

    public function destroy($id) {
        
        $bannerData  = $this->banner->findOrFail($id);

        if(!$bannerData) {
            return redirect()->route('banner.index')->with('message', 'Banner Not found');
        }

        $bannerData->delete();

        return redirect()->route('banner.index')->with('message', 'Banner Deleted Successfully');
    }
}
