<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'UserSchema')]
class UserSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'account_id', title: 'Who is the target of this user', type: 'int')]
    public ?int $accountId;
    #[Property(property: 'email', title: 'confirmed email address for this user, this should be unique -- only one email address registered per instance, multiple users per email are not supported', type: 'string')]
    public ?string $email;
    #[Property(property: 'encrypted_password', title: 'confirmed email address for this user, this should be unique -- only one email address registered per instance, multiple users per email are not supported', type: 'string')]
    public ?string $encryptedPassword;
    #[Property(property: 'signup_ip', title: 'From what IP was this user created', type: 'string')]
    public ?string $signupIp;
    #[Property(property: 'current_signin_at', title: 'When did the user sign in with their current session', type: 'mixed')]
    public mixed $currentSigninAt;
    #[Property(property: 'current_signin_ip', title: 'What/s the previous IP of this user', type: 'string')]
    public ?string $currentSigninIp;
    #[Property(property: 'signin_count', title: 'How many times has this user signed in', type: 'int')]
    public ?int $signinCount;
    #[Property(property: 'locale', title: 'In what timezone/locale is this user located', type: 'string')]
    public ?string $locale;
    #[Property(property: 'last_emailed_at', title: 'When was this user last contacted by email', type: 'mixed')]
    public mixed $lastEmailedAt;
    #[Property(property: 'confirmation_token', title: 'What confirmation token did we send this user/what are we expecting back', type: 'string')]
    public ?string $confirmationToken;
    #[Property(property: 'confirmation_sent_at', title: 'When did we send email confirmation to this user', type: 'mixed')]
    public mixed $confirmationSentAt;
    #[Property(property: 'confirmed_at', title: 'When did the user confirm their email address', type: 'mixed')]
    public mixed $confirmedAt;
    #[Property(property: 'is_moderator', title: 'Is this user a moderator', type: 'int')]
    public ?int $isModerator;
    #[Property(property: 'is_admin', title: 'Is this user an admin', type: 'int')]
    public ?int $isAdmin;
    #[Property(property: 'is_disable', title: 'Is this user disabled from posting', type: 'int')]
    public ?int $isDisable;
    #[Property(property: 'is_approve', title: 'Has this user been approved by a moderator', type: 'int')]
    public ?int $isApprove;
    #[Property(property: 'reset_password_token', title: 'The generated token that the user can use to reset their password', type: 'string')]
    public ?string $resetPasswordToken;
    #[Property(property: 'reset_password_sent_at', title: 'When did we email the user their reset-password email', type: 'mixed')]
    public mixed $resetPasswordSentAt;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\User $model)
    {
        $this->id = $model->id;
        $this->accountId = $model->account_id;
        $this->email = $model->email;
        $this->encryptedPassword = $model->encrypted_password;
        $this->signupIp = $model->signup_ip;
        $this->currentSigninAt = $model->current_signin_at;
        $this->currentSigninIp = $model->current_signin_ip;
        $this->signinCount = $model->signin_count;
        $this->locale = $model->locale;
        $this->lastEmailedAt = $model->last_emailed_at;
        $this->confirmationToken = $model->confirmation_token;
        $this->confirmationSentAt = $model->confirmation_sent_at;
        $this->confirmedAt = $model->confirmed_at;
        $this->isModerator = $model->is_moderator;
        $this->isAdmin = $model->is_admin;
        $this->isDisable = $model->is_disable;
        $this->isApprove = $model->is_approve;
        $this->resetPasswordToken = $model->reset_password_token;
        $this->resetPasswordSentAt = $model->reset_password_sent_at;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'account_id' => $this->accountId, 'email' => $this->email, 'encrypted_password' => $this->encryptedPassword, 'signup_ip' => $this->signupIp, 'current_signin_at' => $this->currentSigninAt, 'current_signin_ip' => $this->currentSigninIp, 'signin_count' => $this->signinCount, 'locale' => $this->locale, 'last_emailed_at' => $this->lastEmailedAt, 'confirmation_token' => $this->confirmationToken, 'confirmation_sent_at' => $this->confirmationSentAt, 'confirmed_at' => $this->confirmedAt, 'is_moderator' => $this->isModerator, 'is_admin' => $this->isAdmin, 'is_disable' => $this->isDisable, 'is_approve' => $this->isApprove, 'reset_password_token' => $this->resetPasswordToken, 'reset_password_sent_at' => $this->resetPasswordSentAt, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}