<?php

namespace App\Http\Controllers;

use Auth;
use App\Member;
use App\Post;
use App\Group;
use App\District;
use App\State;
use App\Gender;
use App\Documentmaster;
use Illuminate\Http\Request;
use App\Http\Requests\updateMemberRequest;
use App\Http\Requests\submitMemberRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Mail\ContactMember;
use Illuminate\Support\Facades\Mail;

/* functions directory 
=========================
*   showPage
*   storePhoto
*   createMember
*   searchMember
*   submitSearchMember
*   assignMember
*   submitMember
*   submitAssignMember
*   removeAssignMember
*   submitUnassignMember
*   editAssignedMember
*   submitEditAssignedMember
*   viewMember
*   editMember
*   submitEditMember
*   deleteMember
*   memberSearchGroup
=========================
*/

class MemberController extends Controller
{
    public function showPage(Request $request){
        $page = 'MEMBER';
        return view('members', compact('page'));
    }

    public function storePhoto($request){
        $input = $request->all();
        if($file = $request->file('profile_picture')){
            $name= $file->getClientOriginalName();
            $onlyname = pathinfo($name, PATHINFO_FILENAME);
            $file->move('storage/images', $onlyname);
            
            /* DB entry starts here */
            $input['original_file_name']= $onlyname;
            $input['file_extension'] =  $file->getClientOriginalExtension();
            $input['renamed_file_name'] = Hash::make($name);
            $input['path']= 'storage/images/'.$onlyname;
            $input['organisation_id']= Auth::user()->organisation_id; 
            Documentmaster::create($input);
            return true;
            } 
            else{
                return false;
            } 
        }


    public function createMember(){
        $page = 'CREATEMEMBER';
        $districts = District::where('record_active_flag', 1)->get();
        $states = State::where('record_active_flag', 1)->get();
        $genders = Gender::where('record_active_flag', 1)->get();
        return response()->json([
            'body' => view('members.create',compact('districts', 'states', 'genders'))->render(),
            'sidebar' => view('members.partials.sidebar',compact('page'))->render(),
            'success' => true,
        ]);
    }

    public function searchMember(){
        $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->get();
        $posts = Post::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get();
        $page = "SEARCHMEMBER";
        return response()->json([
            'body' => view('members.search', compact('groups', 'posts'))->render(),
            'sidebar' => view('members.partials.sidebar', compact('page'))->render(),            
            'success' => true,
        ]);
    }

    public function submitSearchMember(Request $request){
        $data = $request->all();

        if(!isset($data['post_id']) && !isset($data['group_id'])){
        $members = Member::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1]);
        $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->get();
        $mode = "NONE";

        
        }

        if(!isset($data['post_id']) && !isset($data['group_id']) && isset($data['member_id'])){
           
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->get();
            $mode = "NONE";

            $members = Member::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->where(DB::raw("CONCAT_WS(' ',first_name,middle_name,last_name)"), 'LIKE', '%'.$data['member_id'].'%');
            }



        if(isset($data['group_id']) && !isset($data['post_id'])){
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['group_id'] ])->get()->first(); 
            $mode = "GRP";
            
            $members= $groups->members();
        }

        if(isset($data['group_id']) && !isset($data['post_id']) && isset($data['member_id']))
        {
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['group_id'] ])->get()->first(); 
            $mode = "GRP";

            $allmembers = Member::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->where(DB::raw("CONCAT_WS(' ',first_name,middle_name,last_name)"), 'LIKE', '%'.$data['member_id'].'%')->get();

            $grpmemarray =[];
            foreach($groups->members as $grpmem){
                $grpmemarray[] = $grpmem;
            }

            
            $allmemsarray = [];
            foreach($allmembers as $allmember){
                $allmemsarray[] = $allmember;
            }
            $grpmemcol = collect($grpmemarray);
            $allmemcol = collect($allmemsarray);
            $members = $allmemcol->diff($grpmemcol);
            
        }

        if(isset($data['group_id']) && isset($data['post_id'])){
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['group_id'] ])->get()->first();
            $post = Post::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['post_id'] ])->get()->first();
            $mode = "GRPPOST";
            $members = $post->members()->where('group_id',$data['group_id']);
        }

        if(isset($data['group_id']) && isset($data['post_id']) && isset($data['member_id'])){
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['group_id'] ])->get()->first();
            $post = Post::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['post_id'] ])->get()->first();
            $mode = "GRPPOST";
            $members = $groups->members()->where(DB::raw("CONCAT_WS(' ',first_name,middle_name,last_name)"), 'LIKE', '%'.$data['member_id'].'%');
        }

        if(!isset($data['group_id']) && isset($data['post_id']) && !isset($data['member_id'])){
            $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->get();
            $post = Post::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['post_id'] ])->get()->first();
            $members = $post->members();
            $mode ="PST";
        }


        /* $group = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $data['group_id']])->get()->first();
        if(isset($data['post_id'])){
            $post = Post::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id,'id' => $data['post_id']])->get()->first();            
        }else{
            $post = Post::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get();
        } */

        $memberlist = $members->paginate(10);
       
        return response()->json([
            'body' => view('members.member-search-result', compact('memberlist', 'groups', 'mode'))->render(),
            'success' => true,
        ]);
    }

    public function assignMember($pid, $gid){
        $members = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get();
        $post = Post::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $pid])->get()->first();
        $group = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $gid ])->get()->first();
        return response()->json([
            'body' => view('members.assign', compact('members', 'post','group'))->render(),
            'success' => true,
        ]);
    }

    public function submitMember(submitMemberRequest $request){
        try{
            $data = $request->all();
            $data['organisation_id'] = Auth::user()->organisation_id;
            if($data['middle_name']){
                $data['fullname'] = $data['first_name'].' '.$data['middle_name'].' '.$data['last_name'];
            }else{
                $data['fullname'] = $data['first_name'].' '.$data['last_name'];
            }

            $this->storePhoto($request);

            $photo =Documentmaster::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get()->last();

            $data['documentmaster_id'] = $photo->id;
            
            Member::create($data);
            $page = 'CREATEMEMBER';
            $districts = District::where('record_active_flag', 1)->get();
            $states = State::where('record_active_flag', 1)->get();
            $genders = Gender::where('record_active_flag', 1)->get();
            if(count($data)>0){
                return response()->json([
                    'message' => 'Member Created Successfully',
                    'success' => true, 
                    'body' => view('members.create',compact('districts', 'states', 'genders'))->render(),
                    'sidebar' => view('members.partials.sidebar',compact('page'))->render(),                  
                ]);
            }else{
                return response()->json([
                    'message' => 'error',
                    'success' => false,
                ]);
            }
            

    }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
    }
    }

    
    public function submitAssignMember(Request $request, $pid, $gid){
        try{
            $data = $request->all();
            $post = Post::where(['id' => $pid, 'record_active_flag' => 1])->get()->first();
            $post->members()->attach($data['member_id'], ['organisation_id' => Auth::user()->organisation_id,'group_id'=> $gid]);
            $group = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1, 'id' => $gid ])->get()->first();
            $group->members()->attach($data['member_id'],['organisation_id' => Auth::user()->organisation_id]);
            return response()->json([
                'message' => 'Member Assigned Successfully',
                'success' => true,
                'body' => view('groups.posts.view', compact('group'))->render(),
                'group_name' => $group->name,
            ]);
        }catch(Exception $ex)
        {
            throw new \Exception($ex->getMessage());
        }
    }

    public function removeAssignMember($pid,$gid,$mid){
       try{
            $post = Post::where(['record_active_flag' => 1, 'id' => $pid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
            $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $member = Member::where(['record_active_flag' => 1, 'id' => $mid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
            
            return response()->json([
                'body' => view('members.unassign', compact('member', 'group', 'post'))->render(),
                'success' =>true
            ]);
            

       }catch(Exception $ex){
       throw new \Exception($ex->getMessage());
       }
    }

    public function submitUnassignMember(Request $request ,$gid,$pid,$mid){
        try{
            $data = $request->all();
            $post = Post::where(['record_active_flag' => 1, 'id' => $pid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
            $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $post->members()->newPivotStatementForId($mid)->where('group_id', $gid)->update(['deleted_at' => DB::raw('NOW()')]);

            $member = Member::where(['record_active_flag' => 1, 'id' => $mid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $member->groups()->newPivotStatementForId($gid) ->update(['deleted_at' => DB::raw('NOW()')]);

            return response()->json([
                'message' => 'Member Unassigned Successfully',
                'success' => true,
                'body' => view('groups.posts.view', compact('group'))->render(),
                'group_name' => $group->name,
            ]);
            

        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }

    public function editAssignedMember($pid, $gid, $mid){
        try{
            $post = Post::where(['record_active_flag' => 1, 'id' => $pid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $currentmember = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id, 'id' => $mid])->get()->first();

            $members = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get();

            return response()->json([
                'body' => view('members.edit-assign', compact('group', 'post', 'currentmember', 'members'))->render(),
                'success' => true,
            ]);
            
        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }

    public function submitEditAssignedMember(Request $request, $pid, $gid){
        try{
            $data = $request->all();
            $post = Post::where(['record_active_flag' => 1, 'id' => $pid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

            $memberId = $data['currentmemberhid'];
            $post->members()->newPivotStatementForId($memberId)->where('group_id', $gid)->update(['member_id' => $data['member_id']]);

            return response()->json([
                'message' => 'Member Updated Successfully',
                'success' => true,
                'body' => view('groups.posts.view', compact('group'))->render(),
                'group_name' => $group->name,
            ]);

        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }

    public function viewMember($mid, $searchmode, $groupIdRec, $postIdRec){
        try{
            $member = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id, 'id' => $mid])->get()->first();
            $mode = 'VIEW';
            return response()->json([
                'body' => view('members.view-member', compact('member', 'mode', 'searchmode','groupIdRec','postIdRec'))->render(),
                'success' => true
            ]);
        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
        
    }

    public function editMember($mid, $searchmode, $groupIdRec, $postIdRec){
        try{
            $member = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id, 'id' => $mid])->get()->first();
            $genders = Gender::where('record_active_flag', 1)->get();
            $districts = District::where('record_active_flag', 1)->get();
            $states = State::where('record_active_flag', 1)->get();
            $mode = 'EDT';
            return response()->json([
                'body' => view('members.view-member', compact('member', 'mode', 'districts', 'states', 'genders', 'searchmode','groupIdRec', 'postIdRec'))->render(),
                'success' => true
            ]);
        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }

    public function submitEditMember(updateMemberRequest $request, $mid, $searchmode,$groupIdRec, $postIdRec){
        try{
            $data = $request->all();
            $member = Member::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id, 'id' => $mid])->get()->first();
            
            if($data['middle_name']){
                $data['fullname'] = $data['first_name'].' '.$data['middle_name'].' '.$data['last_name'];
            }else{
                $data['fullname'] = $data['first_name'].' '.$data['last_name'];
            }

                $stored = $this->storePhoto($request);
                if($stored){
                    $photo = Documentmaster::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get()->last();
                    
                    $data['documentmaster_id'] = $photo->id;
                }
                
                $member->update($data);
                $mode = $searchmode;
                if($mode=="NONE"){
                    $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1])->get();
                    $posts = Post::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id])->get();

                }elseif($mode=='GRP' || $mode == 'GRPPOST'){
                    $groups = Group::where(['organisation_id' => Auth::user()->organisation_id, 'record_active_flag' => 1,'id' => $groupIdRec])->get()->first();
                    $posts = Post::where(['record_active_flag' => 1, 'organisation_id' => Auth::user()->organisation_id, 'id' => $postIdRec])->get()->first();
                }
            
            return response()->json([

                'success' => true,
                'message' => 'Member Updated Successfully!',
                'code' => $member->id,
                'mem' => view('members.update-member-row', compact('member', 'groups', 'mode'))->render(),
            ]);
            

        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }
    
    public function deleteMember($mid){
        try{
            $member = Member::where(['record_active_flag'=>1, 'organisation_id' => Auth::user()->organisation_id, 'id'=> $mid])->get()->first();
            if(count($member->groups)>0){
                return response()->json([
                    'success' => false,
                    'message' => 'Delete is not allowed, as   member is assigned to a group!',
                ]);
            }else{
                $member['record_active_flag'] = 0;
                $member->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Member Deleted Successfully!',
                    'code' => $mid,
                ]);
            }
        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error! Unable to Delete',
            ]);
        }
        

    }

    public function memberSearchGroup($gid){
        $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
        $post = $group->posts;

        return response()->json([
            'body' => $post,
        ]);
    }

    public function viewMemberQuick($mid){
        $member = Member::where(['record_active_flag' => 1, 'id' => $mid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();

        return response()->json([
            'body' => view('members.quick-view', compact('member'))->render(),
            'success' => true,
        ]);
    }

    public function mailAssignMember($mid){
        $member = Member::where(['record_active_flag' => 1, 'id' => $mid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
        $Mode = 'memmail';
        return response()->json([
            'body' => view('members.mail.compose', compact('member','Mode'))->render(),
            'success' => true,
            
        ]);
    }

    public function sendMemberEmail(Request $request){
        $to = $request->mail_to;
        $data = $request->all();
        if(is_array($to)){
            foreach($to as $key => $toone){
                Mail::to($toone)->send(new ContactMember($data,$key));
            }
        }else{
            $key = '';
            Mail::to($to)->send(new ContactMember($data, $key));
        }
        return response()->json([
            'success' => true,
            'message' => 'E-mail sent successfully.'
        ]);
    }

    public function mailGroupMembers($gid){
        try{
            $group = Group::where(['record_active_flag' => 1, 'id' => $gid, 'organisation_id' => Auth::user()->organisation_id])->get()->first();
            $members = $group->members;

            $Mode = 'grpmail';
            return response()->json([
                'body' => view('members.mail.compose', compact('members','Mode'))->render(),
                'success' => true,
            ]);
        }catch(Exception $ex){
        throw new \Exception($ex->getMessage());
        }
    }
}/* End of Class */
