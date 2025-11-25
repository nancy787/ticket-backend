<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BannerAction;
class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_name',
        'page_tittle',
        'image',
        'description',
        'additional_info',
        'faqs'
    ];

    public function bannerAction() {

        return $this->hasMany(BannerAction::class, 'banner_id');
    }
}

