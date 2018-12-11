<?php

namespace Muserpol\Http\Controllers;
use Muserpol\Models\Address;
use Muserpol\Models\Affiliate;
use Muserpol\Models\AffiliateState;
use Muserpol\Models\Category;
use Muserpol\Models\City;
use Muserpol\Models\Degree;
use Muserpol\Models\PensionEntity;
use Muserpol\Models\Contribution\Contribution;
use Muserpol\Models\Contribution\AidContribution;
use Muserpol\Models\Contribution\Reimbursement;
use Muserpol\Models\Contribution\AidReimbursement;
use Illuminate\Http\Request;
use Log;
use Muserpol\Models\RetirementFund\RetFunState;
use Yajra\Datatables\Datatables;
use Muserpol\Models\RetirementFund\RetirementFund;
use Muserpol\Models\QuotaAidMortuary\QuotaAidMortuary;
use Muserpol\Models\AffiliateRecord;
use Muserpol\Helpers\Util;
use Muserpol\Models\AffiliatePoliceRecord;
use Validator;
use Muserpol\Models\Spouse;
use Carbon\Carbon;
use Muserpol\Models\Entities;
use Auth;
use Muserpol\Models\Contribution\DirectContribution;
use Muserpol\Models\ChargeType;
use Muserpol\Models\PaymentType;
use Muserpol\Models\Voucher;
class AffiliateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {           
        // $affiliates = Affiliate::where('registration',null)->where('birth_date','!=',null)->get();        
        // foreach($affiliates as $affiliate) {            
        //     $affiliate->registration = Util::getRegistration(Util::parseBarDate($affiliate->birth_date),$affiliate->last_name,$affiliate->mothers_last_name,$affiliate->first_name,$affiliate->gender);
        //     $affiliate->save();
        // }
        // return ;

        return view('affiliates.index');
    }
    public function getAllAffiliates(Request $request)
    {
        /*$query = Affiliate::take(100)->get();

        return $datatables->collection($query)
                          // ->addColumn('action', 'eloquent.tables.users-action')
                          ->make(true);*/
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'asc';
        $last_name = strtoupper($request->last_name) ?? '';
        $first_name = strtoupper($request->first_name) ?? '';
        $second_name = strtoupper($request->second_name) ?? '';
        $mothers_last_name = strtoupper($request->mothers_last_name) ?? '';
        $surname_husband = strtoupper($request->surname_husband) ?? '';
        $identity_card = strtoupper($request->identity_card) ?? '';
        $degree = strtoupper($request->degree) ?? '';
        $affiliate_state = strtoupper($request->affiliate_state) ?? '';
        //$total=Affiliate::where('identity_card','LIKE',$identity_card.'%')->where('last_name','LIKE',$last_name.'%')->count();
        //$total=6669783;
        //$affiliates = Affiliate::skip($offset)->take($limit)->orderBy($sort,$order)->where('last_name','LIKE',$last_name.'%')->get();

        $total = Affiliate::select('affiliates.id')//,'identity_card','registration','degrees.name as degree','first_name','second_name','last_name','mothers_last_name','civil_status')->
                                ->leftJoin('degrees', 'affiliates.id', '=', 'degrees.id')
                                ->leftJoin('affiliate_states', 'affiliates.affiliate_state_id', '=', 'affiliate_states.id')
                                ->whereRaw("coalesce(affiliates.first_name,'' ) LIKE '$first_name%'")
                                ->whereRaw("coalesce(affiliates.second_name,'' ) LIKE '$second_name%'")
                                ->whereRaw("coalesce(affiliates.last_name,'') LIKE '$last_name%'")
                                ->whereRaw("coalesce(affiliates.mothers_last_name,'') LIKE '$mothers_last_name%'")
                                ->whereRaw("coalesce(affiliates.surname_husband,'') LIKE '$surname_husband%'")
                                ->whereRaw("coalesce(affiliates.identity_card, '') LIKE '$identity_card%'")
                                ->whereRaw("coalesce(upper(degrees.name), '') LIKE '$degree%'")
                                ->whereRaw("coalesce(upper(affiliate_states.name), '') LIKE '$affiliate_state%'")
                                ->count();

        $affiliates = Affiliate::select(
            'affiliates.id',
            'identity_card',
            'registration',
            'first_name',
            'second_name',
            'surname_husband',
            'last_name',
            'mothers_last_name',
            'degrees.name as degree',
            'civil_status',
            'affiliate_states.name as affiliate_state'
        )
                                ->leftJoin('degrees','affiliates.id','=','degrees.id')
                                ->leftJoin('affiliate_states','affiliates.affiliate_state_id','=','affiliate_states.id')
                                ->skip($offset)
                                ->take($limit)
                                ->orderBy($sort,$order)
                                ->whereRaw("coalesce(affiliates.first_name,'' ) LIKE '$first_name%'")
                                ->whereRaw("coalesce(affiliates.second_name,'' ) LIKE '$second_name%'")
                                ->whereRaw("coalesce(affiliates.last_name,'') LIKE '$last_name%'")
                                ->whereRaw("coalesce(affiliates.mothers_last_name,'') LIKE '$mothers_last_name%'")
                                ->whereRaw("coalesce(affiliates.surname_husband,'') LIKE '$surname_husband%'")
                                ->whereRaw("coalesce(affiliates.identity_card, '') LIKE '$identity_card%'")
                                ->whereRaw("coalesce(upper(degrees.name), '') LIKE '$degree%'")
                                ->whereRaw("coalesce(upper(affiliate_states.name), '') LIKE '$affiliate_state%'")
                                ->get();
        return response()->json(['affiliates' => $affiliates->toArray(),'total'=>$total]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $cities = City::all()->pluck('name', 'id');
        $birth_cities = City::all()->pluck('name', 'id');
        $degrees = Degree::all()->pluck('name', 'id');
        return view('affiliates.create', compact('cities', 'birth_cities', 'degrees'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $verificate = true;
        $due_date = null;
        $rules = [
            'identity_card' => 'required|unique:affiliates',
            'city_identity_card_id' => 'required|min:1',
            'first_name' => 'required|min:1',
            'gender' => 'required',
            'birth_date' => 'required',
            'category_id' => 'required',
            'degree_id' => 'required',
            'last_name' => '',
            'mothers_last_name' => '',
            'city_birth_id' => 'required',
            'nua' => 'integer|nullable'
        ];

        $messages = [
            'identity_card.required' => 'Se requiere llenar este campo',
            'identity_card.unique' => 'El carnet introducido ya existe',
            'city_identity_card_id.required' => 'Debe seleccionar una opción',
            'first_name.required' => 'Se requiere llenar este campo',
            'gender.required' => 'Debe seleccionar una opción',
            'birth_date.required' => 'Debe seleccionar una opción',
            'category_id' => 'Debe seleccionar una opción',
            'degree_id' => 'Debe seleccionar una opción',
            'nua.integer' => 'Debe introducir solo números o cero'
        ];
        if(!$request->is_duedate_undefined){
            $due_date = $request->due_date;
            $verificate = false;
        }
        if (! $request->last_name && !$request->mothers_last_name) {
            //only for flash message
            $rules['last_name'] .='required';
            $messages =[
                'last_name.required' => 'El campo Apellido Paterno o Materno es requerido.',
            ];
        }
        $this->validate($request, $rules, $messages);
        Affiliate::create([
            'user_id' => Auth::user()->id,
            'identity_card' => $request->identity_card,
            'last_name' => $request->last_name,
            'mothers_last_name' => $request->mothers_last_name,
            'first_name' => $request->first_name,
            'second_name' => $request->second_name,
            'city_identity_card_id' => $request->city_identity_card_id,
            'surname_husband' => $request->surname_husband,
            'nua' => $request->nua,
            'phone_number' => $request->phone_number,
            'cell_phone_number' => $request->cell_phone_number,
            'due_date' => $due_date,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'civil_status' => $request->civil_status,
            'type' => $request->type,
            'category_id' => $request->category_id,
            'pension_entity_id' => $request->pension_entity_id,
            'degree_id' => $request->degree_id,
            'is_duedate_undefined' => $verificate,
            'city_birth_id' => $request->city_birth_id
        ]);
        return redirect()->route('affiliate.index');
    }

    /**telefono
     * Display the specified resource.
     *
     * @param  \Muserpol\Affiliate  $affiliate
     * @return \Illuminate\Http\Response
     */
    public function show(Affiliate $affiliate)
    {                
        $this->authorize('view',$affiliate);
        $cities = City::all()->pluck('name', 'id');
        $birth_cities = City::all()->pluck('name', 'id');
        $categories = Category::all()->pluck('name', 'id');
        $degrees = Degree::all()->pluck('name', 'id');
        $pension_entities = PensionEntity::all()->pluck('name', 'id');
        $affiliate_states = AffiliateState::all()->pluck('name', 'id');
        $affiliate_records = AffiliateRecord::where('affiliate_id', $affiliate->id)
        ->orderBy('id','desc')
        ->get();
        $affiliate_police_records = AffiliatePoliceRecord::where('affiliate_id', $affiliate->id)
        ->orderByDesc('date')
        ->get();
        // $quota_mortuaries = QuotaAidMortuary::where('affiliate_id', $affiliate->id)->get();
        /*$records_message=[];
        foreach($affiliate_records as $key=>$affiliate_record){
            $records_message[$key]=substr($affiliate_record->message,0,-20);
        }*/
        //return $records_message;
        $quota_mortuaries = [];
        $cuota = null;
        $auxilio = null;
            foreach($quota_mortuaries as $quota_mortuary){
                if($quota_mortuary->procedure_modality->procedure_type->module_id == 4){
                   $cuota = $quota_mortuary;
                }
                if($quota_mortuary->procedure_modality->procedure_type->module_id == 5){
                      $auxilio = $quota_mortuary;
                    }
            }

        $retirement_fund = RetirementFund::where('affiliate_id', $affiliate->id)->where('code', 'not like', '%A%')->first();
        $states = RetFunState::get();
        $nextcode = RetirementFund::where('affiliate_id', $affiliate->id)->where('code','LIKE','%A')->first();
        if(isset($nextcode))
            $nextcode = $nextcode->code;
        else
            $nextcode = "";
        $active_ret_fun = RetirementFund::where('affiliate_id',$affiliate->id)->where('code','NOT LIKE','%A')->first();
        $affiliate->load([
            'city_identity_card:id,first_shortened',
            'city_birth:id,name',
            'affiliate_state',
            'pension_entity:id,name',
            'category',
            'degree'
        ]);
        $direct_contribution = DirectContribution::where('affiliate_id',$affiliate->id)->where('procedure_state_id','1')->first();        
        if (! sizeOf($affiliate->address) > 0) {
            $affiliate->address[] = new Address();
        }
        $affiliate->phone_number = explode(',', $affiliate->phone_number);
        $affiliate->cell_phone_number = explode(',', $affiliate->cell_phone_number);

        $spouse = $affiliate->spouse->first();
        if (!$spouse) {
            $spouse = new Spouse();
        }else{
            $spouse->load([
                'city_identity_card:id,first_shortened',
                'city_birth:id,name',
            ]);
        }

        //GETTIN CONTRIBUTIONS
        $contributions =  Contribution::where('affiliate_id',$affiliate->id)->pluck('total','month_year')->toArray();
        $reimbursements = Reimbursement::where('affiliate_id',$affiliate->id)->pluck('total','month_year')->toArray();

        if($affiliate->date_entry)
            $end = explode('-', Util::parseMonthYearDate($affiliate->date_entry));
        else
            $end = explode('-', '1976-05-01');
        $month_end = $end[1];
        $year_end = $end[0];

        if($affiliate->date_derelict)
            $start = explode('-', Util::parseMonthYearDate($affiliate->date_derelict));
        else
            $start = explode('-', date('Y-m-d'));
        $month_start = $start[1];
        $year_start = $start[0];

        $aid_contributions = AidContribution::where('affiliate_id',$affiliate->id)->pluck('total','month_year')->toArray();
        $aid_reimbursement = AidReimbursement::where('affiliate_id',$affiliate->id)->pluck('total','month_year')->toArray();
        //return  $affiliate->date_death;//Util::parseMonthYearDate($affiliate->date_death);
        
        if($affiliate->date_death)
            $death = explode('/', $affiliate->date_death);
        else
            $death = explode('/', date('d/m/Y'));
        
        $month_death = $death[1];
        $year_death = $death[2];
        
        $is_editable = "1";
        if(isset($retirement_fund->id) && $retirement_fund->procedure_modality_id != 4 && $retirement_fund->procedure_modality_id != 1)
        {
            $is_editable = "0";
        }
        $quota_aid = $affiliate->quota_aid_mortuaries->last();
        $pension_entities = PensionEntity::all()->pluck('name', 'id');

        
        $charge = ChargeType::where('module_id',Util::getRol()->module_id)->first();
        $payment_types = PaymentType::get();
        $vouchers = Voucher::where('affiliate_id',$affiliate->id)->where('voucher_type_id',2)->with(['type'])->get();
        //return $vouchers;
        $data = array(
            'quota_aid'=>$quota_aid,
            'retirement_fund'=>$retirement_fund,
            'affiliate'=>$affiliate,
            'spouse'=>$spouse,
            'cities'=>$cities,
            'birth_cities'=>$birth_cities,
            'categories'=>$categories,
            'degrees'=>$degrees,
            'pension_entities' =>$pension_entities,
            'affiliate_states'=>$affiliate_states,
            'cuota'=>$cuota,
            'states' => $states,
            'auxilio'=>$auxilio,
            'affiliate_records'=>$affiliate_records,
            'affiliate_police_records'=>$affiliate_police_records,
            'nextcode'  =>  $nextcode,
            'has_ret_fun'   =>  isset($active_ret_fun->id)?true:false,
            'contributions' =>  $contributions,
            'aid_contributions' =>  $aid_contributions,
            'month_end' =>  $month_end,
            'month_start'  =>   $month_start,
            'year_end'  =>  $year_end,
            'year_start'    =>  $year_start,
            'month_death'   =>  $month_death,
            'year_death'    =>  $year_death,
            'reimbursements'    =>  $reimbursements,
            'aid_reimbursements'    =>  $aid_reimbursement,
            'is_editable'   =>  $is_editable,
            'pension_entities' => $pension_entities,
            'has_direct_contribution' => isset($direct_contribution)?true:false,
            'direct_contribution'   =>  $direct_contribution,
            'charge' => $charge,
            'payment_types' =>  $payment_types,
            'vouchers'  =>  $vouchers,
            //'records_message'=>$records_message
        );
        return view('affiliates.show')->with($data);
        //return view('affiliates.show',compact('affiliate','affiliate_states', 'cities', 'categories', 'degrees','degrees_all', 'pension_entities','retirement_fund'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Muserpol\Affiliate  $affiliate
     * @return \Illuminate\Http\Response
     */
    public function edit(Affiliate $affiliate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Muserpol\Affiliate  $affiliate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $affiliate =  Affiliate::where('id','=', $affiliate->id)->first();
        $this->authorize('update', $affiliate);
        /*
        TODO
        add regex into identity_card validate: 51561 and 4451-1L
        */
        $rules = [
            'identity_card' => 'required|min:1',
            'city_identity_card_id' => 'required|min:1',
            'first_name' => 'required|min:1',
            'last_name' => '',
            'mothers_last_name' => '',
            'gender' => 'required',
            'birth_date' => 'required',
        ];
        $messages = [
        ];

        if (! $request->last_name && !$request->mothers_last_name) {
            //only for flash message
            $rules['last_name'] .='required';
            $messages =[
                'last_name.required' => 'El campo Apellido Paterno o Materno es requerido.',
            ];
        }
        try {
            $validator = Validator::make($request->all(), $rules, $messages)->validate();
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Error',
                'errors' => $exception->errors(),
            ], 403);
        }

        $affiliate->identity_card = $request->identity_card;
        $affiliate->first_name = $request->first_name;
        $affiliate->second_name = $request->second_name;
        $affiliate->last_name = $request->last_name;
        $affiliate->mothers_last_name = $request->mothers_last_name;
        $affiliate->gender = $request->gender;
        $affiliate->civil_status = $request->civil_status;
        $affiliate->birth_date = Util::verifyBarDate($request->birth_date) ? Util::parseBarDate($request->birth_date) : $request->birth_date;
        $affiliate->date_death = Util::verifyBarDate($request->date_death) ? Util::parseBarDate($request->date_death) : $request->date_death;
        $affiliate->reason_death = $request->reason_death;
        $affiliate->phone_number = trim(implode(",", $request->phone_number));
        $affiliate->cell_phone_number = trim(implode(",", $request->cell_phone_number));
        $affiliate->city_birth_id = $request->city_birth_id;
        $affiliate->city_identity_card_id =$request->city_identity_card_id;
        $affiliate->surname_husband = $request->surname_husband;        

        if (sizeOf($affiliate->address) > 0) {
            $address_id = $affiliate->address()->first()->id;
            $address = Address::find($address_id);

            foreach ($request->address as $value) {
                if ($value['zone'] || $value['street'] || $value['number_address']) {
                    $address->city_address_id = $value['city_address_id'];
                    $address->zone = $value['zone'];
                    $address->street = $value['street'];
                    $address->number_address = $value['number_address'];
                    $address->save();
                }else{
                    $affiliate->address()->detach($address->id);
                    $address->delete();
                }
            }

        }else{
            if (sizeOf($request->address) > 0) {
                foreach ($request->address as $value) {
                    if ($value['zone'] || $value['street'] || $value['number_address']) {
                        Log::info('zoneee '.$value['zone']);
                        $address = new Address();
                        $address->city_address_id = $value['city_address_id'];
                        $address->zone = $value['zone'];
                        $address->street = $value['street'];
                        $address->number_address = $value['number_address'];
                        $address->save();
                        $affiliate->address()->save($address);
                    }
                }
            }
        }

        $affiliate->identity_card = mb_strtoupper($affiliate->identity_card);
        $affiliate->first_name = mb_strtoupper($affiliate->first_name);
        $affiliate->second_name = mb_strtoupper($affiliate->second_name);
        $affiliate->last_name = mb_strtoupper($affiliate->last_name);
        $affiliate->mothers_last_name = mb_strtoupper($affiliate->mothers_last_name);
        $affiliate->surname_husband = mb_strtoupper($affiliate->surname_husband);        

        $affiliate->save();
        $affiliate = Affiliate::with('address')->find($affiliate->id);
        if (!sizeOf($affiliate->address) > 0) {
            $affiliate->address[] = new Address();
        }
        $affiliate->phone_number = explode(',', $affiliate->phone_number);
        $affiliate->cell_phone_number = explode(',', $affiliate->cell_phone_number);
        $datos=array('affiliate' => $affiliate ,'city_birth' => $affiliate->city_birth,'city_identity_card' => $affiliate->city_identity_card);
        return $datos;

    }
    public function update_affiliate_police(Request $request, Affiliate $affiliate)
    {   
        $affiliate = Affiliate::where('id','=', $affiliate->id)->first();
        $this->authorize('update', $affiliate);
        $affiliate->affiliate_state_id = $request->affiliate_state_id;
        $affiliate->type = $request->type;
        $affiliate->date_entry = Util::verifyMonthYearDate($request->date_entry) ? Util::parseMonthYearDate($request->date_entry) : $request->date_entry;;
        $affiliate->item = $request->item;
        $affiliate->category_id = $request->category_id;
        $affiliate->degree_id = $request->degree_id;
        $affiliate->pension_entity_id = $request->pension_entity_id;
        $affiliate->date_derelict = Util::verifyMonthYearDate($request->date_derelict) ? Util::parseMonthYearDate($request->date_derelict) : $request->date_derelict;
        $affiliate->file_code = mb_strtoupper($request->file_code);
        $affiliate->save();

        $datos = array('affiliate'=>$affiliate,'state'=>$affiliate->affiliate_state,'category'=>$affiliate->category,'degree'=>$affiliate->degree,'pension_entity'=>$affiliate->pension_entity,'file_code'=>$affiliate->file_code);
        return $datos;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Muserpol\Affiliate  $affiliate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Affiliate $affiliate)
    {
        //
        $this->authorize('delete', $affiliate);
    }

    public function printVoucher(){
        
    }
}
