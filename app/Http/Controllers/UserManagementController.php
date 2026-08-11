<?php
namespace App\Http\Controllers;
use App\Models\{Role,User};use Illuminate\Http\Request;use Illuminate\Support\Facades\Hash;use Illuminate\Validation\Rule;use Illuminate\Validation\Rules\Password;
class UserManagementController extends Controller
{
 public function index(Request $r){return view('admin.users.index',['users'=>User::with('roles')->where('company_id',$r->user()->company_id)->whereNull('deleted_at')->orderBy('name')->paginate(25)]);}
 public function create(Request $r){return view('admin.users.form',['record'=>new User,'roles'=>$this->roles($r)]);}
 public function store(Request $r){$data=$this->validateUser($r);$user=User::create(['company_id'=>$r->user()->company_id,'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password'=>Hash::make($data['password']),'is_active'=>$r->boolean('is_active'),'password_changed_at'=>null]);$user->roles()->sync($data['roles']);return redirect()->route('admin.users.index')->with('success','Staff account created. The user must change the temporary password at first login.');}
 public function edit(Request $r,$user){$record=User::with('roles')->where('company_id',$r->user()->company_id)->findOrFail($user);return view('admin.users.form',['record'=>$record,'roles'=>$this->roles($r)]);}
 public function update(Request $r,$user){$record=User::where('company_id',$r->user()->company_id)->findOrFail($user);$data=$this->validateUser($r,$record);$active=$record->id===$r->user()->id?true:$r->boolean('is_active');$values=['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'is_active'=>$active];if(!empty($data['password'])){$values['password']=Hash::make($data['password']);$values['password_changed_at']=null;}$record->update($values);$record->roles()->sync($data['roles']);return redirect()->route('admin.users.index')->with('success','Staff account updated.');}
 private function roles(Request $r){return Role::where('company_id',$r->user()->company_id)->orderBy('name')->get();}
 private function validateUser(Request $r,?User $record=null):array{$company=$r->user()->company_id;return $r->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($record?->id)],'phone'=>['nullable','string','max:30'],'password'=>[$record?'nullable':'required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()],'roles'=>['required','array','min:1'],'roles.*'=>['integer',Rule::exists('roles','id')->where(fn($q)=>$q->where('company_id',$company))],'is_active'=>['nullable','boolean']]);}
}
