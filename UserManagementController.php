<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Http\Requests\UserRequest;
use App\Usertype;
use App\Usergroup;
use App\Userrole;
use App\UsergroupUser;

class UserManagementController extends Controller
{

    // show all organisations
    public function userManagementPage(){
        return view('user.user_management_page',['page'=>'USER_MANAGEMENT']);
    }

    public function createUser(Request $request){
        $userlist = User::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' => view('user.create_user_page',['userlist'=>$userlist])->render(),
            'sidebar' => view('user.sidebar',['page'=>'CREATEUSER'])->render(),
            'success' => true,
        ]);
    }
    public function showUserForm(Request $request,$code){
      $mode = 'INS';
      $user  = null;
      $users = User::where(['remember_token'=>$code])->get();
      $userTypes = Usertype::where(['custom1'=>1])->get();
      $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
      if(count($users)>0){
        $mode = 'EDT';
        $user = $users->first();
      }
      return response()->json([
          'body' => view('user.user_form_page',['mode'=>$mode,'user'=>$user,'types'=>$userTypes,'groups'=>$grouplist])->render(),
          'success' => true,
      ]);
    }

    function saveUser(UserRequest $request,$code){
      try {
        $data = $request->request->all();
        $users = User::where(['remember_token'=>$code])->get();
        $message = 'User has been registered.';
        if(count($users) == 0 && empty($data['password'])){
          return response()->json([
              'message' => 'Password cannot be empty',
              'success' => false,
          ]);
        }else
        if(count($users) == 0 && $data['password'] != $data['cpassword']){
          return response()->json([
              'message' => 'Password is not matching with confirm password.',
              'success' => false,
          ]);
        }else{
          $user = new User();
          $flag = false;
          if(count($users)>0){
              $user = $users->first();
              $message = 'User details has been updated.';
              $flag = true;
          }else{
              $user->remember_token = encrypt('token'.time());
              $user->password = bcrypt($data['password']);
          }
          $user->name = $data['name'];
          $user->email = $data['email'];
          $user->mobile_number = $data['contact'];
          $user->address = $data['address'];
          $user->organisation_id = $request->session()->get('orgid');
          $user->user_type_id = $data['userType'];
          $user->user_name = $data['username'];
          $user->save();
          if($flag){
            $user->groups()->detach();
            $user->groups()->attach($request->get('group'),['organisation_id'=>$request->session()->get('orgid')]);
          }else{
            $user->groups()->attach($request->get('group'),['organisation_id'=>$request->session()->get('orgid')]);
          }
          $userlist = User::where(['organisation_id'=>$request->session()->get('orgid')])->get();
          return response()->json([
              'body' =>  view('user.create_user_page',['userlist'=>$userlist])->render(),
              'message' => $message,
              'success' => true,
          ]);
        }

      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    function deleteUser($code,Request $request){
      try {
        $user = User::where(['remember_token'=>$code])->first();
        $user->delete();
        $userlist = User::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_page',['userlist'=>$userlist])->render(),
            'message' => 'Record has been deleted.',
            'success' => true,
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    function createUserGroup(Request $request){
      try {
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_group_page',['grouplist'=>$grouplist])->render(),
            'sidebar' => view('user.sidebar',['page'=>'CREATEUSERGRP'])->render(),
            'success' => true,
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    public function showUserGroupForm(Request $request,$code){
      $mode = 'INS';
      $grp  = null;
      $grps = Usergroup::where(['remember_token'=>$code])->get();
      if(count($grps)>0){
        $mode = 'EDT';
        $grp = $grps->first();
      }
      return response()->json([
          'body' => view('user.user_group_form_page',['mode'=>$mode,'grp'=>$grp])->render(),
          'success' => true,
      ]);
    }
    function saveUserGroup(UserRequest $request,$code){
      try {
        $message = '';
        $grps = Usergroup::where(['remember_token'=>$code])->get();
        if(count($grps)>0){
          $grp = $grps->first();
          $grp->updates($request);
          $message = 'Record has been updated.';
        }else{
          $grp = new Usergroup();
          $grp->inserts($request);
          $message = 'Record has been saved.';
        }
        $grp->save();
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_group_page',['grouplist'=>$grouplist])->render(),
            'success' => true,
            'message' => $message
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }
    function deleteUserGroup($code,Request $request){
      try {
        $grps = Usergroup::where(['remember_token'=>$code])->first();
        $grps->delete();
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_group_page',['grouplist'=>$grouplist])->render(),
            'success' => true,
            'message' => 'Record has been deleted.'
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }
    // user roles page
    function createUserRolePage(Request $request){
      try {
        $rolelist  = Userrole::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_role_page',['rolelist'=>$rolelist])->render(),
            'sidebar' => view('user.sidebar',['page'=>'CREATEUSERROLE'])->render(),
            'success' => true,
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    public function showUserRoleForm(Request $request,$code){
      $mode = 'INS';
      $role  = null;
      $roles = Userrole::where(['remember_token'=>$code])->get();
      if(count($roles)>0){
        $mode = 'EDT';
        $role = $roles->first();
      }
      return response()->json([
          'body' => view('user.user_role_form_page',['mode'=>$mode,'role'=>$role])->render(),
          'success' => true,
      ]);
    }

    function saveUserRole(UserRequest $request,$code){
      try {
        $message = '';
        $roles = Userrole::where(['remember_token'=>$code])->get();
        if(count($roles)>0){
          $role = $roles->first();
          $role->updates($request);
          $message = 'Record has been updated.';
        }else{
          $role = new Userrole();
          $role->inserts($request);
          $message = 'Record has been saved.';
        }
        $role->save();
        $rolelist  = Userrole::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_role_page',['rolelist'=>$rolelist])->render(),
            'success' => true,
            'message' => $message
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    function deleteUserRole($code,Request $request){
      try {
        $role = Userrole::where(['remember_token'=>$code])->first();
        $role->delete();
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        $rolelist  = Userrole::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_role_page',['rolelist'=>$rolelist])->render(),
            'success' => true,
            'message' => 'Record has been deleted.'
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    // group roles page
    function createGroupRolePage(Request $request){
      try {
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_group_list_page',['grouplist'=>$grouplist])->render(),
            'sidebar' => view('user.sidebar',['page'=>'CREATEGROUPROLE'])->render(),
            'success' => true,
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }

    }

    public function showGroupRoleAssignForm(Request $request,$code){
      $grp = Usergroup::where(['remember_token'=>$code])->first();
      $roles = Userrole::where(['record_active_flag'=>1])->get();
      return response()->json([
          'body' => view('user.user_assign_role_form',['roles'=>$roles,'grp'=>$grp])->render(),
          'success' => true,
      ]);
    }

    function saveGroupRoleAssignForm(Request $request){
      try {
        $grp = Usergroup::where(['remember_token'=>$request->request->get('groupid')])->first();
        $grp->roles()->detach();
        $grp->roles()->attach($request->request->get('role'),['organisation_id'=>$request->session()->get('orgid')]);
        $grouplist  = Usergroup::where(['organisation_id'=>$request->session()->get('orgid')])->get();
        return response()->json([
            'body' =>  view('user.create_user_group_list_page',['grouplist'=>$grouplist])->render(),
            'success' => true,
            'message' => 'Assignment has been done.'
        ]);
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }
    }


}
