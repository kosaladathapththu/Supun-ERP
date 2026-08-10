<?php
namespace App\Http\Controllers;
use App\Models\{Account,JournalEntry,JournalLine};use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;
class AccountingController extends Controller
{
 public function journals(Request $r){$q=JournalEntry::where('company_id',$r->user()->company_id);if($r->filled('from'))$q->whereDate('entry_date','>=',$r->from);if($r->filled('to'))$q->whereDate('entry_date','<=',$r->to);return view('accounting.journals',['entries'=>$q->latest('entry_date')->latest('id')->paginate(25)->withQueryString()]);}
 public function show(Request $r,JournalEntry $journal){abort_unless($journal->company_id===$r->user()->company_id,404);$journal->load('lines.account');return view('accounting.show',compact('journal'));}
 public function trialBalance(Request $r){$company=$r->user()->company_id;$to=$r->input('to',now()->toDateString());$accounts=Account::with('type')->where('company_id',$company)->where('is_active',1)->orderBy('code')->get();$totals=JournalLine::select('account_id',DB::raw('SUM(debit) debit_total'),DB::raw('SUM(credit) credit_total'))->whereHas('entry',fn($q)=>$q->where('company_id',$company)->where('status','posted')->whereDate('entry_date','<=',$to))->groupBy('account_id')->get()->keyBy('account_id');return view('accounting.trial-balance',compact('accounts','totals','to'));}
 public function ledger(Request $r,Account $account){abort_unless($account->company_id===$r->user()->company_id,404);$lines=JournalLine::with('entry')->where('account_id',$account->id)->whereHas('entry',fn($q)=>$q->where('status','posted'))->get()->sortBy(fn($x)=>$x->entry->entry_date->format('Y-m-d').str_pad($x->id,10,'0',STR_PAD_LEFT));return view('accounting.ledger',compact('account','lines'));}
}
