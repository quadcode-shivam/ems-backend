<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'check_ins';

    // Define the primary key for the model
    protected $primaryKey = 'id';

    // Specify which attributes are mass assignable
    protected $fillable = [
        'employee_id',
        'check_in_time',
        'check_out_time',
        'check_in_info',
        'check_out_info',
        'status'
    ];

    // Optionally, you can disable timestamps if your table does not have `created_at` and `updated_at` columns
    // public $timestamps = false;

    // Optionally, you can define custom date formats for your attributes
    // protected $dates = ['check_in_time', 'check_out_time'];

    // Optionally, you can define any relationships with other models here
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'user_id');
    }
}
