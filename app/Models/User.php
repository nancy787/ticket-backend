<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Subscription;
use App\Models\StripeConnectAccount;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'age',
        'address',
        'country',
        'nationality',
        'sign_up_as',
        'google_id',
        'google_token',
        'device_type',
        'is_premium',
        'is_blocked',
        'phone_number',
        'is_free_subcription',
        'fcm_token',
        'notifications_enabled',
        'apple_id',
        'apple_token',
        'subscription_expire_date',
        'faq',
        'chat_enabled',
        'app_version',
        'is_subscribed',
        'is_stripe_connected'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ...

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // public function permissions()
    // {
    //     return $this->belongsToMany(Permission::class);
    // }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
    
    public function stripeConnect() {
        return $this->hasOne(StripeConnectAccount::class);
    }

    public function getFirstNameAttribute() {
        $parts = explode(' ', $this->name, 2);
        return $parts[0] ?? null;
    }

    public function getLastNameAttribute()
    {
        $parts = explode(' ', $this->name, 2);
        return $parts[1] ?? null;
    }

    public function setFirstNameAttribute($value)
    {
        $lastName = $this->last_name ?? '';
        $this->attributes['name'] = trim("$value $lastName");
    }

    public function setLastNameAttribute($value)
    {
        $firstName = $this->first_name ?? '';
        $this->attributes['name'] = trim("$firstName $value");
    }
}
