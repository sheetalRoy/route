<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Folder;
use Auth;
use App\File;
use App\FileFolder;
//use App\Http\Controllers\Schema;

class FileController extends Controller
{
    public function showPage(){
        $page = 'FILE';
        return view('files', compact('page'));
    }
    /* View files and folder in form of jstree */
    public function viewFiles(){
        try {
            $uType =Auth::user()->user_type_id;
            $uId =Auth::user()->id;
            /* Give Persmision for super admin */
            if($uType ==1){
             $temp = DB::table('folders')
               ->select(DB::raw("id,(CASE WHEN parent_folder_id IS NULL THEN '#' ELSE parent_folder_id END) as parent,name as text"))
               // ->where('record_active_flag',1)
               ->get();   
            }else{
               $temp = DB::table('folders')
               ->select(DB::raw("id,(CASE WHEN parent_folder_id IS NULL THEN '#' ELSE parent_folder_id END) as parent,name as text"))
                ->where('created_by',$uId)
                // ->where('record_active_flag',1)
               ->get();
           }
        return response()->json([
            'data' => $temp,
            'body' => view('files.view_tree_folder_file',['folderId'=>0])->render(),
            'sidebar' =>view('files.partials.sidebar')->render(),
            'success' => true,
        ]);
        } catch (Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Connection was fail! Please Try again.']);
        }
    }
    /* Load create folder Home Page */
    public function loadCreateFolder(){
        $uId =Auth::user()->id;
        $folderDetails = Folder::where(['parent_folder_id'=> null,'created_by'=>$uId,'record_active_flag'=>1])->get();
        return response()->json([
            'body' => view('files.create_folder_main_page',['parentFolder'=>$folderDetails])->render(),
            'sidebar' =>view('files.partials.sidebar')->render(),
            'success' => true,
        ]);
    }
    
    /* Load Folder Details creating page */
    public function loadCreateNewFolder($parentId){
        //$folderDetails = Folder::where(['parent_folder_id'=> null])->get();
        return response()->json([
            'body' => view('files.create_folder_modal',['parentId'=>$parentId])->render(),
            'sidebar' =>view('files.partials.sidebar')->render(),
            'success' => true,
        ]);
    }
    /* View SUB-FOLDER in form of TREE structure */
    public function loadCreateSubFolder($parentId){
        $resarr = [];
        $temp = DB::table('folders')
               ->select(DB::raw("id,(CASE WHEN parent_folder_id = $parentId THEN '#' ELSE parent_folder_id END) as parent,name as text"))
               ->where('parent_folder_id',$parentId)
               ->where('record_active_flag',1)
               ->get();
        $resarr = collect($temp)->toArray();
        if(count($resarr)>0){
            foreach ($resarr as $key => $value) {
                $subitems = $this->getChildItems($value->id);
                $resarr = array_merge_recursive($resarr,$subitems);
            }
        }
        return response()->json([
            'data' => $resarr,
            'body' => view('files.viewfile',['folderId'=>$parentId])->render(),
            'success' => true,
        ]);
    }
    function getChildItems($parentId){
        $resarr = [];
        $temp = DB::table('folders')
               ->select(DB::raw("id,parent_folder_id as parent,name as text"))
               ->where('parent_folder_id',$parentId)
               ->where('record_active_flag',1)
               ->get();
        $resarr = collect($temp)->toArray();
        if(count($resarr)>0){
            foreach ($resarr as $key => $value) {
                $subitems = $this->getChildItems($value->id);
                $resarr = array_merge_recursive($resarr,$subitems);
            }
        }

        return $resarr;
    }
    /* Save folder Name */
    public function saveFolderName(Request $request){
         DB::beginTransaction();
        try {
            $uId =Auth::user()->id;

        $parentId = $request->request->get('parent_id');
        $folderName = $request->request->get('folderName');
        /* Create folder inside public */
        $path = public_path('download/file/'.$folderName);
        $result = Storage::makeDirectory($folderName);
        $description = $request->request->get('description');
        $folderDetails = new Folder();
        $folderDetails->organisation_id = Auth::user()->organisation_id;
        if($parentId > 0){
            $folderDetails->parent_folder_id = $parentId;
        }
        $folderDetails->name = $folderName;
        $folderDetails->description =$description;
        $folderDetails->created_by = $uId;
        $folderDetails->save();
          DB::commit();
         if($parentId==0){
            $folderDetails = Folder::where(['parent_folder_id'=> null,'created_by'=>$uId,'record_active_flag'=>1])->get();
        return response()->json([
            'body' => view('files.create_folder_main_page',['parentFolder'=>$folderDetails])->render(),
            'sidebar' =>view('files.partials.sidebar')->render(),
            'message' => 'Folder name saved successfully',
            'success' => true,
        ]);
        }else{
         while($parentId!=''){
         $checkParentFolder = Folder::find($parentId);
         $superParent = $checkParentFolder->parent_folder_id;
         $parentId = $superParent;
        }
        $superParentId = $checkParentFolder->id;
        $resultArr = $this->getSubFolder($superParentId);
        // echo var_dump($resultArr);die();
        
        return response()->json([
            'data' => $resultArr,
            'body' => view('files.viewfile',['folderId'=>$parentId])->render(),
            'message' => 'Folder name saved successfully',
            'success' => true,
        ]);        
    }
       
        } catch (Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Connection was fail! Please Try again.']);
            DB::rollBack();
        }
    }
    function getSubFolder($parentId){
        $resarr = [];
        $temp = DB::table('folders')
               ->select(DB::raw("id,(CASE WHEN parent_folder_id = $parentId THEN '#' ELSE parent_folder_id END) as parent,name as text"))
               ->where('parent_folder_id',$parentId)
               ->where('record_active_flag',1)
               ->get();
        $resarr = collect($temp)->toArray();
        if(count($resarr)>0){
            foreach ($resarr as $key => $value) {
                $subitems = $this->getChildItems($value->id);
                $resarr = array_merge_recursive($resarr,$subitems);
            }
        }
        return $resarr;
        
    }
    
    /* SHOW FILE */
        public function loadShowFile($mode, $folderId){
            try{
                // $file = FileFolder::where(['folder_id' => $folderId])->get();
         $fileId = DB::table('files')
        ->join('file_folder', 'files.id', '=', 'file_folder.file_id')
        ->where('file_folder.folder_id','=',$folderId)
        ->where('files.record_active_flag','=',1)
        ->select('files.original_file_name as fileName',
            'files.renamed_file_name as rename','files.id as fileId')->get();
        // echo $fileId;die();
       return response()->json([
             'body' => view('files.loadFileContainer',['fileName'=>$fileId,'mode'=>$mode])->render(),
            'success' => true,
        ]);
            }catch(Exception $ex){

            }
        
        }
    /* upload file when click on AddFile button from jstree */
    public function fileUploadClick(request $request){
        $folderId = $request->request->get('inputfolderId');
        $folderObj = Folder::find($folderId);
        $folderName = $folderObj->name;
        // $dir = Storage::directories($folderName);

        // var_dump($dir);die();
        if($request->hasFile('file')){
            $file = $request->file('file');
            $name= $file->getClientOriginalName();
            $extension = $file->extension();
            // $path = $file->path();// System path eg:C/user/doc/...
            $path = public_path('download/file/'.$name);
            $fileDetails = new File();
            $fileDetails->original_file_name =$name;
            $fileDetails->file_extension = $extension;
            $fileDetails->path = $path;
            $fileDetails->save();
            Storage::putFile($folderName,$file);//Store in file folder
            // echo 7687;die();
              $file->move('download/file', $name);
            $fileFolderTxn = new FileFolder();
             $fileFolderTxn->file_id = $fileDetails->id;
             $fileFolderTxn->folder_id = $folderId;
             $fileFolderTxn->save();
       
        $fileId = DB::table('files')
        ->join('file_folder', 'files.id', '=', 'file_folder.file_id')
        ->where('file_folder.folder_id','=',$folderId)
        ->where('files.record_active_flag','=',1)
        ->select('files.original_file_name as fileName','files.renamed_file_name as rename','files.id as fileId')->get();
       return response()->json([
             'body' => view('files.loadFileContainer',['fileName'=>$fileId,'mode'=>'view'])->render(),
            'success' => true,
        ]);
        }else{
            return 'No file';
         
        //return $request->image->store('public');
        }
    }
    /* Rename folder */
    public function renameFolder($folderId){
        $folderId =Folder::find($folderId);
        
        return response()->json([
             'body' => view('files.rename_folder_modal',['folderDetails'=>$folderId])->render(),
            'success' => true,
        ]);
    }
    /*Update folder Name */
    public function updateFolderName(request $request){
        try{
        $uId =Auth::user()->id;
        $folderId = $request->request->get('fId');
        $folderName = $request->request->get('folderName');
        $folderObj = Folder::find($folderId);
        $folderObj->name = $folderName;
        $folderObj->save();
        $folderDetails = Folder::where(['parent_folder_id'=> null,'created_by'=>$uId])->get();
        while($folderId!=''){
         $checkParentFolder = Folder::find($folderId);
         $superParent = $checkParentFolder->parent_folder_id;
         $folderId = $superParent;
        }
        $superParentId = $checkParentFolder->id;
        $resultArr = $this->getSubFolder($superParentId);
        // $resultArr = $this->getSubFolder($folderId);
        return response()->json([
            'data' => $resultArr,
            'body' => view('files.viewfile',['folderId'=>$folderId])->render(),
            'success' => true,
        ]);        
    }catch(Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Connection was fail! Please Try again.']);
            
    }
    }
    /* Delete folder Information */
    public function deleteFolderInfo(request $request,$folderId){
         $uId =Auth::user()->id;
         $folderObj = Folder::find($folderId);
         /* get the child to be deleted */
         // $childObj = Folder::where(['parent_folder_id'=>$folderId])->get();
         // foreach ($childObj as  $child) {
         //     $child->record_active_flag = 0;
         // }
         
         $folderObj->record_active_flag = 0;
         $folderObj->save();
         while($folderId!=''){
         $checkParentFolder = Folder::find($folderId);
         $superParent = $checkParentFolder->parent_folder_id;
         $folderId = $superParent;
        }
        $superParentId = $checkParentFolder->id;
         $resultArr = $this->getSubFolder($superParentId);
         // var_dump($resultArr);die();
        return response()->json([
            'data' => $resultArr,
            'parentId' => $superParentId,
            'body' => view('files.viewfile',['folderId'=>$folderId])->render(),
            'success' => true,
        ]);        
    }
    /* Delete file Information */
    public function deleteFileInfo($fileId){
        DB::beginTransaction();
        try{
            $fileFolder = FileFolder::find($fileId);
            $folderId = $fileFolder->folder_id;
            $fileObj = File::find($fileId);
            $fileObj->record_active_flag = 0;
            $fileObj->save();
            DB::commit();
            $result = $this->getFileName($folderId);
            return response()->json([
                'body' => view('files.loadFileContainer',['fileName'=>$result,'mode'=>'view'])->render(),
                'success' =>true,
            ]);
        }catch(Exception $ex){
            DB::rollBack();
        }
    }
    function getFileName($folderId){
        $fileObj = DB::table('files')
        ->join('file_folder', 'files.id', '=', 'file_folder.file_id')
        ->where('file_folder.folder_id','=',$folderId)
        ->where('files.record_active_flag','=',1)
        ->select('files.original_file_name as fileName','files.renamed_file_name as rename','files.id as fileId')->get();
        return $fileObj;
    }
    /* Edit FileName(Rename) */
    public function editFileName($fileId){
        try{
            $fileObj = File::find($fileId);
            return response()->json([
        'body' => view('files.edit_file_modal',['fileName'=>$fileObj])->render(),
                'success' =>true,
        ]);
        }catch(Exception $ex){

        }
    }
    /* Update file Name */
    public function updateFileName(request $request){
         DB::beginTransaction();
        try{
            $fileId = $request->request->get('fId');
         $name = $request->request->get('renameFile');
        $newPath = public_path('download/file/'.$name);
        $fileDetails = File::find($fileId);
        $oldPath =$fileDetails->path;
        // $fileDetails->original_file_name =$name;
        $fileDetails->renamed_file_name =$name;
        $url = Storage::url($oldPath);
         // echo $url;die();
        //$fileDetails->renamed_file_name = $name;   //Rename file name 
        // $fileDetails->path = $newPath;

        $fileDetails->save();
        // Storage::putFile('public',$name);
        // Storage::move($url, $newPath);
        DB::commit();
         $fileFolder = FileFolder::where(['file_id'=>$fileId])->first();
         $folderId = $fileFolder->folder_id;
         $result = $this->getFileName($folderId);
        return response()->json([
        'body' => view('files.loadFileContainer',['fileName'=>$result,'mode'=>'view'])->render(),
                'success' =>true,
        ]);
        }catch(Exception $ex){
            DB::rollBack();
        }
        
    }
    /* Load trash file&folder */
    public function loadTrashFile(){
        // echo 787878;die();
        // $fileObj = FileFolder::where(['record_active_flag'=>0])->get();
        $fileObj = File::where(['record_active_flag'=>0])->get();
        $folderObj = Folder::where(['record_active_flag'=>0])->get();
        return response()->json([
        'body' => view('files.trash_page',['fileObj'=>$fileObj,'folderObj'=>$folderObj])->render(),
        'sidebar' =>view('files.partials.sidebar')->render(),
                'success' =>true,
        ]);
    }
    /* Restore file & folder */
    public function restoreFileFolder($fileId, $mode){
       try{
        if($mode=='file'){
            $fileFolder = File::find($fileId);
        $fileFolder->record_active_flag = 1;
        $fileFolder->save();    
        }else{
            $fileFolder = Folder::find($fileId);
        $fileFolder->record_active_flag = 1;
        $fileFolder->save();
        }
        $fileObj = File::where(['record_active_flag'=>0])->get();
        $folderObj = Folder::where(['record_active_flag'=>0])->get();
        return response()->json([
        'body' => view('files.trash_page',['fileObj'=>$fileObj,'folderObj'=>$folderObj])->render(),
                'success' =>true,
        ]);
       }catch(Exception $ex){
       
       }
    }
    /* Permanent delete file from db */
    public function emptyFile($fileId){
        try{
            $file = File::find($fileId);
            $file->delete();
            $fileObj = File::where(['record_active_flag'=>0])->get();
            $folderObj = Folder::where(['record_active_flag'=>0])->get();
        return response()->json([
        'body' => view('files.trash_page',['fileObj'=>$fileObj,'folderObj'=>$folderObj])->render(),
                'success' =>true,
        ]);
        }catch(Exception $ex){

        }
    }
    /* Permanent delete folder from db */
    public function emptyFolder($folderId){
        try{
            $folder = Folder::find($folderId);
            $folder->delete();
            $fileObj = File::where(['record_active_flag'=>0])->get();
        $folderObj = Folder::where(['record_active_flag'=>0])->get();
        return response()->json([
        'body' => view('files.trash_page',['fileObj'=>$fileObj,'folderObj'=>$folderObj])->render(),
        'success' =>true,
    ]);
        }catch(Exception $ex){

        }
    }
    /* Load add-file-to-folder main page */
    public function loadAddFileFolder($folderId){
        echo 8787;die();
        return response()->json([
            'body' => view('files.add_file_to_folder',['folderId'=>$folderId])->render(),
            'sidebar' =>view('files.partials.sidebar')->render(),
            'success' => true,
        ]);
    }
    /* Upload File */
    public function uploadFile(request $request){
        $folderId = $request->request->get('folderId');
        //return $request->uploadBox->store('public');
       //
        if($request->hasFile('uploadBox')){
            $file = $request->file('uploadBox');
            $name= $file->getClientOriginalName();
            $extension = $file->extension();
            $path = $file->path();
            $fileDetails = new File();
            $fileDetails->original_file_name =$name;
            $fileDetails->file_extension = $extension;
            $fileDetails->path = $path;
            $fileDetails->save();
            echo 878;die();
             // Storage::putFile('public',$name);//Store in file folder
            $file->move('storage/file', $name);
            //storage_path('app/public/file',$name);
            $fileFolderTxn = new FileFolder();
            $fileFolderTxn->file_id = $fileDetails->id;
            $fileFolderTxn->folder_id = $folderId;
            $fileFolderTxn->save();
        return response()->json([
            'message' => 'Record saved successfully',
            'success' => true,
        ]);
        }else{
            return 'No file';
         
        //return $request->image->store('public');
        }
    
       
    }
    /* Archive Files */
    public function archiveFiles($folderId){
        try{
            $folderObj = Folder::find($folderId);
            $folderName = $folderObj->name;
            $files = array();
            // $absolutePath = public_path('download/file/');
            $absolutePath = storage_path('app/'.$folderName.'/');
            // echo $path;die();
            // $absolutePath = $_SERVER['DOCUMENT_ROOT'] . "/JCRE/web/uploads/Documents/";
            $files = scandir($absolutePath);
            // var_dump($files);die();
            $zip = new \ZipArchive();
            $zipName = $folderName . Date('Y-m-d') . ".zip";
            $zip->open($zipName, \ZipArchive::CREATE);
            foreach ($files as $f) {
                if (!is_dir($f)) {
                    $Content = $zip->addFromString(basename($f), file_get_contents($absolutePath . $f));
                }
            }
             $extractPath = getenv("HOMEDRIVE") . getenv("HOMEPATH") . '\Downloads\Archive_' . Date('Y-m-d');
            // // move_uploaded_file($tmp_name, "$uploads_dir/$name");
            // $extractPath ='/Home/Downloads';
            // $extractPath = Storage::download('hjhj');
            if ($zip->open($zipName) != "true") {
                echo "Error :- Unable to open the Zip File";
            }
            /* Extract Zip File */
            $zip->extractTo($extractPath);
            $zip->close();
            // header('Content-Type: application/zip');
            // header("Content-Disposition: attachment; filename = $download");
            // header('Content-Length: ' . filesize($zipName));
            // header("Location: $zipName");
            // echo 898;die();
            return response()->json([
            'message' => 'Your Back Up file has been stored successfully',
            'success' => true,
            ]);
            // $response = new Response(file_get_contents($zipName));
            // $response->headers->set('Content-Type', 'application/zip');
            // $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            // $response->headers->set('Content-length', filesize($zipName));

            // header('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            // header("Content-Length: ".filesize($zipName));
            // readfile($zipName);
            // Storage::move($extractPath'.jpg', 'new/file.jpg');
        }catch(Exception $ex){

        }

    }
    /* Download Selected folder from tree include files*/
    public function downloadSelectFolder($folderId){
        try{
            $folderObj = Folder::find($folderId);
            $folderName = $folderObj->name;
            $files = array();
            $absolutePath = storage_path('app/'.$folderName.'/');
            $files = scandir($absolutePath);
            $zip = new \ZipArchive();
            $zipName = $folderName . Date('Y-m-d') . ".zip";
            $zip->open($zipName, \ZipArchive::CREATE);
            foreach ($files as $f) {
                if (!is_dir($f)) {
                    $Content = $zip->addFromString(basename($f), file_get_contents($absolutePath . $f));
                }
            }
                if ($zip->open($zipName) != "true") {
                return "Error :- Unable to open the Zip File";
            }
            return response()->download( public_path() . '/' . $zipName);
        }catch(Exception $ex){

        }
    }

    public function getFolderName($folder_id){
        $folder = Folder::find($folder_id);
        $folderName = $folder->name;
        return response()->json([
            'folder_name' => $folderName,
        ]);
    }
}

