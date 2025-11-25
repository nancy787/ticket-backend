<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Banner;

class BannerAction extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
