<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TermsAndCondition;

class ProfileController extends Controller
{

    public $termsAndCondition;

    public function __construct(TermsAndCondition $termsAndCondition){
        $this->termsAndCondition = $termsAndCondition;
    }

    public function termsAndCondition() {

        $termAndCondition  =  $this->termsAndCondition->pluck('content')->first();

        return response()->json([
            'success' => true,
            'termsAndCondition' => $termAndCondition,
        ], 200);

    }

    public function helpAndSupport() {
        $helpAndSupport = view('setting.banner.help_and_support')->render();

        return response()->json([
            'success' => true,
            'helpAndSupport' => $helpAndSupport,
        ], 200);

    }

    public function privacyPolicy() {
        $privacyPolicy = view('setting.banner.privacy-policy')->render();

        return response()->json([
            'success' => true,
            'privacyPolicy' => $privacyPolicy,
        ], 200);

    }

}
