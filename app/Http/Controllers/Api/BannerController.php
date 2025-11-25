<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\BannerAction;

class BannerController extends Controller
{
    protected $banner;

    public function __construct(Banner $banner, BannerAction $bannerAction) {

        $this->banner = $banner;
        $this->bannerAction = $bannerAction;
    }

    public function index(Request $request)
    {
        try {
            $banners = $this->banner->with('bannerAction')->get();

            if ($banners->isEmpty()) {
                return response()->json(['message' => 'No banners found'], 200);
            }

            return response()->json(['banners' => $banners], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch banners: ' . $e->getMessage()
            ], 500);
        }
    }
}
