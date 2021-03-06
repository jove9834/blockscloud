<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\Helper;
use App\Models\Post;
use App\Models\PostCate;
use App\Models\PostRelationships;
use App\User;

class PostController extends CommonController
{

    // 获取列表
    public function index(Request $request)
    {
        // 获取当前页码
        $page      = $request->input('page');
        $name      = $request->input('name');

        $query = Post::query()->skip(($page-1)*10)->take(10)->where('status', '<>', -1)->orderBy('created_at', 'desc');
        $totalQuery = Post::query()->where('status', '<>', -1);
        if($name) {
            $query = $query->where('name',$name);
            $totalQuery = $totalQuery->where('name',$name);
        }
        $lists = $query->get()->toArray();
        $total = $totalQuery->count();

        foreach ($lists as $key => $value) {
            $userInfo = User::where('id',$value['uid'])->first();
            $lists[$key]['username'] = $userInfo->name;
            $postRelationshipsLists = PostRelationships::where('object_id',$value['id'])->get();
            $postCateName = '';
            foreach ($postRelationshipsLists as $relationshipsKey => $relationshipsValue) {
                $postCateInfo = PostCate::where('id',$relationshipsValue->post_cate_id)->first();
                $postCateName = $postCateName.','.$postCateInfo->name;
            }
            $lists[$key]['post_cate_name'] = trim($postCateName,',');
        }
        if($lists) {
            $data['lists'] = $lists;
            $data['total'] = $total;
            return Helper::jsonSuccess('获取成功！','',$data);
        } else {
            return Helper::jsonSuccess('获取失败！');
        }
    }

    // 添加信息
    public function create(Request $request)
    {
        $lists = PostCate::all()->toArray();
        $tree = Helper::listToTree($lists);
        $orderList = Helper::treeToOrderList($tree);
        $data = [];
        foreach ($orderList as $key => $value) {
            $data[$key]['value'] = $value['id'];
            $data[$key]['name'] = $value['name'];
        }
        if ($data) {
            return Helper::jsonSuccess('操作成功！','',$data);
        } else {
            return Helper::jsonError('操作失败！');
        }
    }

    // 添加信息
    public function store(Request $request)
    {
        $userInfo  = Auth::user();
        $data['uid'] = $userInfo->id;
        $data['uuid'] = Helper::createUuid();
        $data['title'] = $request->input('title');
        $data['name'] = '';
        $data['description'] = $request->input('description');
        $data['content'] = $request->input('content');
        $data['password'] = '';
        $data['status'] = $request->input('status');
        $data['pid'] = 0;
        $data['level'] = 0;
        $data['type'] = 'post';
        $data['comment'] = 0;
        $data['view'] = 0;

        $checkedPostCates = $request->input('checkedPostCates');
        $cover_path = $request->input('cover_path');

        $result = Post::create($data);
        if ($result) {
            foreach ($checkedPostCates as $key => $value) {
                $postRelationshipsData['object_id'] = $result->id;
                $postRelationshipsData['post_cate_id'] = $value;
                $postRelationshipsData['sort'] = 0;
                PostRelationships::create($postRelationshipsData);
            }

            return Helper::jsonSuccess('操作成功！');
        } else {
            return Helper::jsonError('操作失败！');
        }
    }

    // 编辑信息
    public function edit(Request $request)
    {
        $id = $request->input('id');
        $result = Post::where('id',$id)->first();
        if ($result) {
            return Helper::jsonSuccess('获取成功！','',$result);
        } else {
            return Helper::jsonError('获取失败，请重试！');
        }
    }

    // 执行编辑信息
    public function update(Request $request)
    {
        $id = $request->input('id');
        $data['name'] = $request->input('name');
        $data['email'] = $request->input('email');
        $password = $request->input('password');

        if(!empty($password)) {
            $data['password'] = bcrypt($password);
        }

        $result = Post::where('id',$id)->update($data);
        if ($result) {
            return Helper::jsonSuccess('操作成功！');
        } else {
            return Helper::jsonError('操作失败！');
        }
    }

    // 设置状态
    public function setStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if($status == -1) {
            $result = Post::where('id',$id)->delete();
        } else {
            $result = Post::where('id',$id)->update(['status'=>$status]);
        }

        if ($result) {
            return Helper::jsonSuccess('操作成功！');
        } else {
            return Helper::jsonError('操作失败！');
        }
    }

    // 设置多选状态
    public function setAllStatus(Request $request)
    {
        $status = $request->input('status');
        $selection = $request->input('selection');

        foreach ($selection as $key => $value) {
            $ids[] = $value['id'];
        }

        if($status == -1) {
            $result = Post::whereIn('id',$ids)->delete();
        } else {
            $result = Post::whereIn('id',$ids)->update(['status'=>$status]);
        }

        if ($result) {
            return Helper::jsonSuccess('操作成功！');
        } else {
            return Helper::jsonError('操作失败！');
        }
    }

}