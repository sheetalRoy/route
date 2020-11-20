<?php
use App\Documentmaster;
use App\User;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

/* Route::get('/test', function () {
return view('welcome');
}); */

/** Create Organisation Management Part begins  */
Route::get('/admin/organisations', 'OrganisationController@organisationsPage')->name('_show_organisations')->middleware('auth');
Route::get('/admin/organisation/add/new', 'OrganisationController@addNewOrganisation')->name('_add_organisation_page')->middleware('auth');
Route::get('/admin/organisation/edit/{code}', 'OrganisationController@editOrganisation')->name('_edit_organisation_page')->middleware('auth');
Route::post('/admin/organisation/save/{id}', 'OrganisationController@saveOrganisation')->name('_save_organisation')->middleware('auth');
Route::get('/admin/organisationlist', 'OrganisationController@getOrganisationList')->name('_organisation_list')->middleware('auth');
Route::get('/admin/organisation/go/{code}', 'OrganisationController@loginOrganisation')->name('_organisation_login')->middleware('auth');
/** User management section **/
Route::get('/user.management', 'UserManagementController@userManagementPage')->name('_show_user_management_page')->middleware('auth');
Route::get('/user.createpage', 'UserManagementController@createUser')->name('_create_user_page')->middleware('auth');
Route::get('/user.createform/{code}', 'UserManagementController@showUserForm')->name('_create_user_form')->middleware('auth');
Route::post('/user.saveuser/{code}', 'UserManagementController@saveUser')->name('_save_user')->middleware('auth');
Route::get('/user.delete/{code}', 'UserManagementController@deleteUser')->name('_delete_user')->middleware('auth');
Route::get('/usergroup.createpage', 'UserManagementController@createUserGroup')->name('_create_user_group_page')->middleware('auth');
Route::get('/usergroup.createform/{code}', 'UserManagementController@showUserGroupForm')->name('_create_user_group_form')->middleware('auth');
Route::post('/usergroup.saveusergroup/{code}', 'UserManagementController@saveUserGroup')->name('_save_user_group')->middleware('auth');
Route::get('/usergroup.delete/{code}', 'UserManagementController@deleteUserGroup')->name('_delete_user_group')->middleware('auth');
Route::get('/userrole.createpage', 'UserManagementController@createUserRolePage')->name('_create_user_role_page')->middleware('auth');
Route::get('/userrole.createform/{code}', 'UserManagementController@showUserRoleForm')->name('_create_user_role_form')->middleware('auth');
Route::post('/userrole.saveuserrole/{code}', 'UserManagementController@saveUserRole')->name('_save_user_role')->middleware('auth');
Route::get('/userrole.delete/{code}', 'UserManagementController@deleteUserRole')->name('_delete_user_role')->middleware('auth');
Route::get('/grouprole.grouplistpage', 'UserManagementController@createGroupRolePage')->name('_create_group_role_page')->middleware('auth');
Route::get('/grouprole.assignform/{code}', 'UserManagementController@showGroupRoleAssignForm')->name('_assign_group_role_form')->middleware('auth');
Route::post('/grouprole.saveassignment', 'UserManagementController@saveGroupRoleAssignForm')->name('_save_group_role_assignment')->middleware('auth');

Route::get('/', function () {
    $page = 'DASHBOARD';
    return view('dashboard', compact('page'));
})->middleware(['auth','checkorg']);

Route::get('/groups', 'GroupController@showPage')->middleware('auth');
Route::get('/create-group', 'GroupController@createGroup')->middleware('auth');
Route::get('/view-group', 'GroupController@viewGroup')->middleware('auth');
Route::post('/submit-group', 'GroupController@submitGroup')->middleware('auth');

Route::get('/create-post', 'GroupController@createPost')->middleware('auth');

Route::get('/all-post', 'GroupController@allPost')->middleware('auth');
Route::get('/show-post-by-date-range/{gid}', 'GroupController@showPostByDateRange')->middleware('auth');
Route::get('/view-post/{gid}', 'GroupController@viewPost')->middleware('auth');
Route::post('/submit-post', 'GroupController@submitPost')->middleware('auth');
Route::get('/delete-group', 'GroupController@deleteGroup')->middleware('auth');
Route::post('submit-delete-group', 'GroupController@submitDeleteGroup')->middleware('auth');
Route::get('/update-group', 'GroupController@updateGroup')->middleware('auth');
Route::post('/submit-update-group', 'GroupController@submitUpdateGroup')->middleware('auth');
Route::get('/update_group_select/{gid}', 'GroupController@updateGroupSelect')->middleware('auth');
Route::get('/edit-post/{pid}/{gid}', 'GroupController@editPost')->middleware('auth');
Route::post('/submit-edit-post/{pid}/{gid}', 'GroupController@submitEditPost')->middleware('auth');
Route::post('/submit-remove-post/{pid}/{gid}', 'GroupController@submitRemovePost')->middleware('auth');
Route::get('/remove-post/{pid}/{gid}', 'GroupController@removePost')->middleware('auth');

/* 6/2/18 */
Route::get('/view-assign-post/{pid}/{gid}', 'GroupController@viewAssignPost')->middleware('auth');
Route::get('/assign-post-to-group/{gid}', 'GroupController@assignPostToGroup')->middleware('auth');

/* 8/3/18 */
Route::post('/submit-assign-post/{gid}', 'GroupController@submitAssignPost')->middleware('auth');

Route::get('/view-assign-member-history/{pid}/{gid}', 'GroupController@viewAssignMemberHistory')->middleware('auth');

Route::get('/delete-post/{pid}', 'GroupController@deletePost')->middleware('auth');
Route::get('/force-end-group/{gid}', 'GroupController@forceEndGroup')->middleware('auth');

Route::get('/post-history/{gid}', 'GroupController@postHistory')->middleware('auth');

/* GROUP HISTORY */
Route::get('/show-group-history', 'GroupController@showGroupHistory')->middleware('auth');
Route::get('/view-group-history/{gid}', 'GroupController@viewGroupHistory')->middleware('auth');
Route::get('/delete-group-history/{gid}', 'GroupController@deleteGroupHistory')->middleware('auth');


Route::get('/members', 'MemberController@showPage')->middleware('auth');
Route::get('/view-member-quick/{mid}', 'MemberController@viewMemberQuick')->middleware('auth');
Route::post('/submit-member', 'MemberController@submitMember')->middleware('auth');

Route::get('/create-member', 'MemberController@createMember')->middleware('auth');
Route::get('/search-member', 'MemberController@searchMember')->middleware('auth');
Route::get('/submit-search-member', 'MemberController@submitSearchMember')->middleware('auth');
Route::get('/assign-member/{pid}/{gid}', 'MemberController@assignMember')->middleware('auth');
Route::post('/submit-assign-member/{pid}/{gid}', 'MemberController@submitAssignMember')->middleware('auth');
Route::get('/remove-assign-member/{pid}/{gid}/{mid}', 'MemberController@removeAssignMember')->middleware('auth');
Route::post('/submit-unassign-member/{pid}/{gid}/{mid}', 'MemberController@submitUnassignMember')->middleware('auth');
Route::get('/edit-assigned-member/{pid}/{gid}/{mid}', 'MemberController@editAssignedMember')->middleware('auth');
Route::post('/submit-edit-assign-member/{pid}/{gid}', 'MemberController@submitEditAssignedMember')->middleware('auth');
Route::get('/member-search-group/{gid}','MemberController@memberSearchGroup')->middleware('auth');
Route::get('/view-member/{mid}/{searchmode}/{groupIdRec}/{postIdRec}', 'MemberController@viewMember')->middleware('auth');
Route::get('/edit-member/{mid}/{searchmode}/{groupIdRec}/{postIdRec}', 'MemberController@editMember')->middleware('auth');
Route::get('/delete-member/{mid}', 'MemberController@deleteMember')->middleware('auth');
Route::post('/submit-edit-member/{mid}/{searchmode}/{groupIdRec}/{postIdRec}', 'MemberController@submitEditMember')->middleware('auth');
Route::post('/sorting-submit/{gid}', 'GroupController@tableSort');
/* Email Function */
Route::get('/mail-assign-member/{mid}', 'MemberController@mailAssignMember')->middleware('auth');
Route::post('send-member-email', 'MemberController@sendMemberEmail')->middleware('auth');
Route::get('/mail-group-members/{gid}', 'MemberController@mailGroupMembers')->middleware('auth');
/* Email Function Ends */

Route::get('/view-file', 'FileController@viewFiles')->middleware('auth');
Route::get('/files', 'FileController@showPage')->middleware('auth');

/* Folder and Files section */
Route::get('/create-folder', 'FileController@loadCreateFolder')->middleware('auth');
Route::get('/create-new-folder/{parentId}', 'FileController@loadCreateNewFolder')->middleware('auth');
Route::get('/create-sub-folder/{parentId}', 'FileController@loadCreateSubFolder')->middleware('auth');
Route::post('/save-folder-name', 'FileController@saveFolderName')->middleware('auth');
Route::get('/add-file-to-folder/{folderId}', 'FileController@loadAddFileFolder')->middleware('auth');
Route::get('/show-file/{mode}/{folderId}', 'FileController@loadShowFile')->middleware('auth');
Route::post('/upload-file', 'FileController@uploadFile')->middleware('auth');

Route::post('/file-upload-click', 'FileController@fileUploadClick')->middleware('auth');
/* 6/3/18 */
Route::get('/rename-folder/{folderId}', 'FileController@renameFolder')->middleware('auth');
Route::post('/update-folderName', 'FileController@updateFolderName')->middleware('auth');
Route::get('/delete-folder/{folderId}', 'FileController@deleteFolderInfo')->middleware('auth');
Route::get('/delete-fileName/{fileId}', 'FileController@deleteFileInfo')->middleware('auth');
/* Route for edit should be added */
Route::get('/edit-fileName/{fileId}', 'FileController@editFileName')->middleware('auth');
Route::post('/update-fileName', 'FileController@updateFileName')->middleware('auth');
Route::get('/load-trash', 'FileController@loadTrashFile')->middleware('auth');
Route::get('/restore-file-folder/{fileId}/{mode}', 'FileController@restoreFileFolder')->middleware('auth');
Route::get('/permanent-delete-file/{fileId}', 'FileController@emptyFile')->middleware('auth');
/* 150318 */
Route::get('/permanent-delete-folder/{folderId}', 'FileController@emptyFolder');
Route::get('/archive-files/{folderId}', 'FileController@archiveFiles');
Route::get('/download-sel-folder/{folderId}', 'FileController@downloadSelectFolder')->middleware('auth');
/* 240418 */
Route::get('/get-folder-name/{folder_id}', 'FileController@getFolderName');
/* End File section  */

Route::get('/create', function () {
    $user = User::find(1);
    $docmas = new Documentmaster(['original_file_name' => 'Test']);
    $user->documentmasters()->save($docmas, ['organisation_id' => 1]);
});

Auth::routes();
//Route::get('/home', 'HomeController@index')->name('home');
