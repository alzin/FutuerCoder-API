<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Str;



class User extends Authenticatable implements MustVerifyEmail

{
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'timeZone',
        'email_verified_at',
        'age',
        'verification_token',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function sendEmailVerificationNotification($verificationToken = null, $userId = null)
    {
        $token = $verificationToken ?: $this->verification_token;
        $id = $userId ?: $this->id;

        $this->notify(new VerifyEmailNotification($token, $id));
    }

    public static function boot()
    {
        parent::boot();
    
        static::creating(function ($user) {
            if (empty($user->verification_token)) {
                $user->verification_token = Str::random(64);
            }
        });
    }


}
