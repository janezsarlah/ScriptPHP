<?php

class ClientsController extends BaseController {
    protected $layout = "layouts.main";
    protected $user;
    protected $userData;
    protected $data = array();
    
    public function __construct()
    {
        $this->beforeFilter('csrf', array('on'=>'post'));
        
        $userInfo = new UserInfo(Sentry::getUser());
        $this->user = $userInfo;
        $this->userData = $userInfo->getUserInfo();
        $data = array();
        $data['userInfo'] = $userInfo->getUserInfo();
        
        $arrayPermissions = array(
            '1' => 'menu/dashboard',
            '2' => 'menu/projects',
            '3' => 'menu/clients',
            '4' => 'menu/invoices',
            '5' => 'menu/support',
            '6' => 'menu/settings',
            '7' => 'clients/edit',
            '8' => 'clients/add',
            '9' => 'clients/delete',
            '10' => 'clients/delete/all',
            '11' => 'clients/addContacts',
            '12' => 'clients/delete/all/contacts',
        );
        
        $data['userAccess'] = $userInfo->getArrayAccess($arrayPermissions);
        View::share($data);
    }
   
    public function showClients()
    {
        $idUporabnika = $this->getUserPrimaryID();
        
        $this->data['clients'] = Clients::where('user_id','=',$idUporabnika)->get();
        
        $this->layout->content = View::make('clients.list',$this->data);
    }
    
    public function addClient()
    {
        $this->layout->content = View::make('clients.add',$this->data);   
    }
    
    public function saveClient()
    {
        $rules = array(
			'company_name'    => 'required', 
			'address' => 'required',
			'zip_code' => 'required',
			'city' => 'required',
            'country' => 'required',
		);
    
        $validator = Validator::make(Input::all(), $rules);
        
        if ($validator->fails()) {
            Session::flash('clientsAddError', '1');
			return Redirect::to('clients/add')
				->with( 'message', Lang::get('basic.errorFormMessage')) // send back all errors to the login form
				->withInput(); // send back the input (not the password) so that we can repopulate the form
		} else {
            
               if($this->userData['parent_id'] != 0)
               {
                    $getPrimaryUser = Users::where('id','=',$this->userData['parent_id'])->first();
                    $idUporabnika = $getPrimaryUser->id;
               }
               else//Gre za glavnega uporabnika
               {
                    $idUporabnika = $this->userData['id'];
               }
            
              $client = new Clients;
              $client->user_id = $idUporabnika;
              $client->company_name = Input::get('company_name');
              $client->address = Input::get('address');
              $client->zip_code = Input::get('zip_code');
              $client->city = Input::get('city');
              $client->country = Input::get('country');
              $client->tax_id = Input::get('tax_id');
              $client->website = Input::get('website');
              $client->phone = Input::get('phone');
              $client->email = Input::get('email');
              $client->mobile = Input::get('mobile');
             
              if ( $client->save() ) {
                Session::flash('clientsSuccess', '1');
                return Redirect::to( 'clients/list' )->with( 'message', Lang::get('clients.successAdd') );
              } else {
                 Session::flash('clientsAddError', '1'); 
                 return Redirect::to( 'clients/add' )->with( 'message', Lang::get('clients.errorOnSaving'));
              }
            
        }
    }
    
    public function editClient($id)
    {
        $clientID = Hashids::decrypt($id);
        
        //Gre za zaposlenega ali pod uporabnika glavnega uporabnika
        $idUporabnika = $this->getUserPrimaryID();
        
        //Preverimo če uporabnik ureja svoje stranke
        $dobiStranko = Clients::find($clientID[0]);
        
        if($dobiStranko->user_id != $idUporabnika)
        {
            Session::flash('clientsError','1');
            return Redirect::to( 'clients/list' )->with( 'message', Lang::get('clients.errorOtherClient') );
        }
        else
        {
            $this->data['clientID'] = $id;
            $this->data['clientInfo'] = $dobiStranko;
            $this->layout->content = View::make('clients.edit',$this->data);   
        } 
    }
    
    public function updateClient()
    {
        $hashClientID = Input::get('cValue');
        $clientID = Hashids::decrypt(Input::get('cValue'));
        
        $idUporabnika = $this->getUserPrimaryID();
        
        $dobiStranko = Clients::find($clientID[0]);
        
        if($dobiStranko->user_id != $idUporabnika)
        {
            Session::flash('clientsEditError','1');
            return Redirect::to( 'clients/edit/'.$hashClientID.'' )->with( 'message', Lang::get('clients.errorOtherClient') );
        }
        else
        {
             $rules = array(
                'company_name'  => 'required', 
                'address' => 'required',
                'zip_code' => 'required',
                'city' => 'required',
                'country' => 'required',
            );

            $validator = Validator::make(Input::all(), $rules);

            if ($validator->fails()) {
                Session::flash('clientsEditError', '1');
                return Redirect::to('clients/edit/'.$hashClientID.'')
                    ->with( 'message', Lang::get('basic.errorFormMessage')) // send back all errors to the login form
                    ->withInput(); // send back the input (not the password) so that we can repopulate the form
            } else {

                  $client = Clients::find($clientID[0]);
                  $client->user_id = $idUporabnika;
                  $client->company_name = Input::get('company_name');
                  $client->address = Input::get('address');
                  $client->zip_code = Input::get('zip_code');
                  $client->city = Input::get('city');
                  $client->country = Input::get('country');
                  $client->tax_id = Input::get('tax_id');
                  $client->website = Input::get('website');
                  $client->phone = Input::get('phone');
                  $client->email = Input::get('email');
                  $client->mobile = Input::get('mobile');

                  if ( $client->save() ) {
                    Session::flash('clientsSuccess', '1');
                    return Redirect::to( 'clients/list' )->with( 'message', Lang::get('clients.successEdit') );
                  } else {
                     Session::flash('clientsEditError', '1'); 
                     return Redirect::to( 'clients/edit/'.$hashClientID.'' )->with( 'message', Lang::get('clients.errorOnSaving'));
                  }

            }  
        }
        
    } 
    
    public function deleteClient($id)
    {
        $clientID = Hashids::decrypt($id);
        $idUporabnika = $this->getUserPrimaryID();
        
        $dobiStranko = Clients::find($clientID[0]);
        
        if($dobiStranko->user_id != $idUporabnika)
        {
            Session::flash('clientsError','1');
            return Redirect::to( 'clients/list' )->with( 'message', Lang::get('clients.errorOtherClient') );
        }
        else
        {
            if($dobiStranko->delete())
            {
                Session::flash('clientsSuccess', '1');
                return Redirect::to('clients/list')->with( 'message', Lang::get('clients.successDelete') );
                
            }
            else
            {
                Session::flash('clientsError', '1');
                return Redirect::to('clients/list')->with( 'message', Lang::get('clients.errorDelete') );
            }         
        }
    }
    
    public function deleteSelectedClients()
    {
        $clients = Input::get('selected');
        $total = 0;
        $totalClients = sizeof($clients);
        $idUporabnika = $this->getUserPrimaryID();
        
            for($i=0; $i<sizeof($clients);$i++)
            {
                $id = $clients[$i];
                $hashID = Hashids::decrypt($id);
                $deleteClientContacts = ClientContacts::where('client_id','=', $hashID[0])->get();
                $getClient = Clients::find($hashID[0]);

                foreach($deleteClientContacts as $del)
                {
                    $del->delete();   
                }
                if($getClient->delete())
                {
                    $total++;
                }  

                if($total == $totalClients)
                {
                    Session::flash('clientsSuccess', '1');
                    return Redirect::to('clients/list')->with( 'message', Lang::get('clients.successDeleteAll') );
                }
                else
                {
                    Session::flash('clientsError', '1');
                    return Redirect::to('clients/list')->with( 'message', Lang::get('clients.errorDeleteAll') );      
                } 
         }
    }
    
    public function viewClient($id)
    {
        $clientID = Hashids::decrypt($id);
        $idUporabnika = $this->getUserPrimaryID();
        $dobiStranko = Clients::find($clientID[0]);
        if($dobiStranko->user_id != $idUporabnika)
        {

            Session::flash('clientsError','1');
            return Redirect::to( 'clients/list' )->with( 'message', Lang::get('clients.errorOtherClient') );
        }
        else
        {

            $this->data['clientsContacts'] = Clients::find($clientID[0])->clientContacts;
            $this->data['clientsID'] = $id;
            $this->layout->content = View::make('clients.view',$this->data);  
        }
    }
    
    public function saveContact()
    {
        $response=array();
        $hashClientID = Input::get('cvalue');
        $clientID = Hashids::decrypt(Input::get('cvalue'));
        
        $idUporabnika = $this->getUserPrimaryID();
        
        $dobiStranko = Clients::find($clientID[0]);
        
        if($dobiStranko->user_id != $idUporabnika)
        {
            $response = array(
                'error' => true,
                'msg' => 'Nimate pravic za to storitev!');
        }
        else
        {
              $rules = array(
                    'name'  => 'required', 
                    'email' => 'required',
                );

                $validator = Validator::make(Input::all(), $rules);

                if ($validator->fails()) 
                {

                $response = array(
                'error' => true,
                'msg' => 'Polja označena z * so obvezna!');
                } 
                else 
                {
                    if(Input::get('primary')=="1")
                    {
                         $primary = ClientContacts::where('client_id','=',$clientID[0])->where('primary','=','1')->get();

                          if($primary)
                          {
                              foreach($primary as $p)
                              {
                                 $update = ClientContacts::find($p->id);
                                 $update->primary = 0;
                                 $update->save();
                              }
                          }
                    }

                      $clientContacts = new ClientContacts;
                      $clientContacts->client_id=$clientID[0];
                      $clientContacts->name= Input::get('name');
                      $clientContacts->address = Input::get('address');
                      $clientContacts->zip_code = Input::get('zip_code');
                      $clientContacts->city = Input::get('city');
                      $clientContacts->country = Input::get('country');
                      $clientContacts->mobile = Input::get('mobile');
                      $clientContacts->phone = Input::get('phone');
                      $clientContacts->email = Input::get('email');
                      $clientContacts->primary = Input::get('primary');  
                      if($clientContacts->Save())
                      {
                          
                            $response = array(
                       'success' => true,
                       'msg' => 'Uspešno dodajanje kontakta stranke','id'=>$clientID,'contactid'=>Hashids::encrypt($clientContacts->id),'name'=>$clientContacts->name,'address'=>$clientContacts->address,'email'=>$clientContacts->email,'mobile'=>$clientContacts->mobile);
                          
                        
                     }
                  

                }

            
        }
         return Response::json( $response );
    }
    
    private function getUserPrimaryID()
    {
        //Validacija uporabnika
        if($this->userData['parent_id'] != 0)
        {
            $getPrimaryUser = Users::where('id','=',$this->userData['parent_id'])->first();
            $idUporabnika = $getPrimaryUser->id;
        }
        else//Gre za glavnega uporabnika
        {
            $idUporabnika = $this->userData['id'];
        }
        
        return $idUporabnika;
    }
    
    public function DeleteClientContacts($id)
    {
        
        $clientkontaktID = Hashids::decrypt($id);
        $dobikontakt = ClientContacts::find($clientkontaktID[0]);
        $id_client= Hashids::encrypt($dobikontakt->client_id);

        
        $userID=$dobikontakt->client;
        $idUporabnika = $this->getUserPrimaryID();
        
        if($userID->user_id !=$idUporabnika)
        {          
            Session::flash('clientsError','1');
            return Redirect::to( 'clients/view/'.$id.'' )->with( 'message', Lang::get('clients.errorOtherClient') );
        }
        else
        {   
            if($dobikontakt->delete())
            {
                Session::flash('clientContacsSuccess', '1');
                return Redirect::to('clients/view/'.$id_client.'')->with( 'message', Lang::get('clients.successDelete') );
                
            }
            else
            {
                Session::flash('clientContactsError', '1');
                return Redirect::to('clients/view/'.$id.'')->with( 'message', Lang::get('clients.errorDelete') );
            }         
        }   
        
    }
    public function EditClientContacts()
    {
       
        $response=array();
        $id=Input::get('id');
        $clientkontaktID = Hashids::decrypt($id);
        $dobikontakt = ClientContacts::find($clientkontaktID[0]);
        $id_client= Hashids::encrypt($dobikontakt->client_id);

        $userID=$dobikontakt->client;
        $idUporabnika = $this->getUserPrimaryID();
        
        if($userID->user_id !=$idUporabnika)
        {
            $response = array(
                'error' => true
            );
                
        }
        else
        {
            $array=array(
                'id'=>Hashids::encrypt($dobikontakt->id), 
                'name'=>$dobikontakt->name,
                'address'=>$dobikontakt->address,
                'zip_code'=>$dobikontakt->zip_code,
                'city'=>$dobikontakt->city,
                'country'=>$dobikontakt->country,
                'phone'=>$dobikontakt->phone,
                'email'=>$dobikontakt->email,
                'primary'=>$dobikontakt->primary
            );
            $response = array(
                'success' => true,
                'clientobject'=>$array
            );
        
        }
        return json_encode($response);
    }

    public function UpdateEditClientContacts()
    {
        $response=array();
        $clientID = Hashids::decrypt(Input::get('cvalue'));
        $dobikontakt = Hashids::decrypt(Input::get('contactvalue'));
        $rules = array(
                    'name'  => 'required', 
                    'email' => 'required',
                );

                $validator = Validator::make(Input::all(), $rules);

                if ($validator->fails()) 
                {

                $response = array(
                'error' => true,
                'msg' => 'Polja označena z * so obvezna!');
                }
                else
                {
                    if(Input::get('primary')=="1")
                    {
                         $primary = ClientContacts::where('client_id','=',$clientID[0])->where('primary','=','1')->get();
                          if($primary)
                          {
                              foreach($primary as $p)
                              {
                                 $update = ClientContacts::find($p->id);
                                 $update->primary = 0;
                                 $update->save();
                              }
                          }
                    }
                      $clientContacts =ClientContacts::find($dobikontakt[0]);
                      $clientContacts->client_id=$clientID[0];
                      $clientContacts->name= Input::get('name');
                      $clientContacts->address = Input::get('address');
                      $clientContacts->zip_code = Input::get('zip_code');
                      $clientContacts->city = Input::get('city');
                      $clientContacts->country = Input::get('country');
                      $clientContacts->mobile = Input::get('mobile');
                      $clientContacts->phone = Input::get('phone');
                      $clientContacts->email = Input::get('email');
                      $clientContacts->primary = Input::get('primary');  
                      if($clientContacts->Save())
                      { 
                          $polje=array(
                            'id'=>Hashids::encrypt($clientContacts->id), 
                            'name'=>$clientContacts->name,
                            'address'=>$clientContacts->address,
                            'zip_code'=>$clientContacts->zip_code,
                            'city'=>$clientContacts->city,
                            'country'=>$clientContacts->country,
                            'mobile'=>$clientContacts->mobile,
                            'phone'=>$clientContacts->phone,
                            'email'=>$clientContacts->email,
                            'primary'=>$clientContacts->primary
                            );
                            $response = array(
                                'success' => true,
                                'msg' => 'Uspešno urejanje kontakta stranke',
                                'contact'=>$polje,
                            );       
                      }
                       
                }
         return Response::json($response);
    }
    public function deleteSelectedContect()
    {
        $clientID = Input::get('cvalue');
        $contacts = Input::get('selected');//clients
        $total = 0;
        $totalClientsContacts = sizeof($contacts);
        $idUporabnika = $this->getUserPrimaryID();
        for($i=0; $i<sizeof($contacts);$i++)
        {
            $id = $contacts[$i];
            $hashID = Hashids::decrypt($id);
            $checkClientContactsExist = ClientContacts::find($hashID[0]);
            $iduser=$checkClientContactsExist->client;

            if($iduser->user_id== $idUporabnika)
            {
                if($checkClientContactsExist->delete()){
                    $total++;
                }  
            }
            
        }
        
        if($total == $totalClientsContacts)
        {
            Session::flash('clientContactsError', '1');
            return Redirect::to('clients/view/'.$clientID.'')->with( 'message', Lang::get('clients.successDeleteAllContact') );
        }
        else
        {
            Session::flash('clientContactsError', '1');
            return Redirect::to('clients/view/'.$clientID.'')->with( 'message', Lang::get('clients.errorDeleteAllContact') );      
        } 
        
    }
    
}