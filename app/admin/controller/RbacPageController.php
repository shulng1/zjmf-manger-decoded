<?php

namespace app\admin\controller;

/**
 * @title 页面管理
 * @description 接口说明
 */
class RbacPageController extends AdminBaseController
{
    protected $data;
    public function initialize()
    {
        parent::initialize();
    }
    /**
     * @title 页面列表
     * @description 接口说明:页面列表
     * @author 刘国栋
     * @url /admin/rbacpage
     * @method GET
     * @return roles:  菜单列表@
     * @roles  name:页面名称
     * @roles  status:状态
     * @roles  remark:备注
     * @return rule: 权限列表@
     * @throws
     **/
    public function index()
    {
        $param = $this->request->param();
        $order = isset($param["order"][0]) ? trim($param["order"]) : "id";
        $sort = isset($param["sort"][0]) ? trim($param["sort"]) : "DESC";
        $roles = \think\Db::name("role_page")
            ->field("id,name,status,remark")
            ->order($order, $sort)
            ->select()
            ->toArray();
        return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "roles" => $roles]);
    }
    /**
     * @title 页面管理添加（添加权限页面）
     * @description 接口说明:页面管理添加
     * @author 刘国栋
     * @url admin/rbacpage/role_page
     * @method GET
     * @return roles:  菜单列表@
     * @return rule: 权限列表@
     * @rule list:子权限列表@ name:子权限名称 title:子权限标题
     * @list name:权限名称 title:权限标题
     **/
    public function addRolePage()
    {
        $auths = \think\Db::name("auth_rule")->field("id,pid,title")->where("status", 1)->select()->toArray();
        $auths_tree = $this->listToTree($auths);
        return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "auths" => $auths_tree]);
    }
    /**
     * @title 添加页面（添加页面）
     * @description 接口说明:添加页面（添加页面）
     * @author 刘国栋
     * @url admin/rbacpage
     * @method POST
     * @param .name:name type:str require:1  other: desc:名称
     * @param .name:remark type:str require:0  other: desc:描述
     * @param .name:status type:int require:1  other: desc:状态（1：开启，0：禁用）
     * @param .name:auth[] type:int require:0  other: desc:权限ID组
     **/
    public function addRole()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only("name,remark,status,auth");
            $auth = array_filter($data["auth"], function ($x) {
                return $x > 0 && is_numeric($x);
            });
            unset($data["auth"]);
            $rule = ["name" => "require|max:15", "remark" => "max:255", "status" => "require|in:0,1"];
            $msg = ["name.require" => "名称不能为空", "status.require" => "状态不能为空"];
            $validate = new \think\Validate($rule, $msg);
            $validate_result = $validate->check($data);
            if (!$validate_result) {
                return jsonrule(["status" => 400, "msg" => $validate->getError()]);
            }
            if (!empty($auth) && is_array($auth)) {
                $auth = \think\Db::name("auth_rule")->whereIn("id", $auth)->select()->toArray();
                $auth = array_column($auth, "name");
            }
            \think\Db::startTrans();
            try {
                $result = db("role_page")->insertGetId($data);
                $insert = [];
                foreach ($auth as $v) {
                    $insert[] = ["rolepage_id" => $result, "rule_name" => $v, "type" => "admin_url"];
                }
                if (!empty($insert)) {
                    \think\Db::name("authpage_access")->insertAll($insert);
                }
                \think\Db::commit();
                active_log(sprintf($this->lang["Rabcpage_admin_addRole"], $result));
            } catch (\Exception $e) {
                \think\Db::rollback();
                return jsonrule(["status" => 400, "msg" => lang("ADD FAIL")]);
            }
            return jsonrule(["status" => 200, "msg" => lang("ADD SUCCESS")]);
        }
        return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
    }
    /**
     * @title 编辑页面页面（编辑页面页面）
     * @description 接口说明:编辑页面页面（编辑页面页面）
     * @author 刘国栋
     * @url admin/rbacpage/:id
     * @method GET
     * @param .name:id type:str require:1  other: desc:页面ID
     * @param .name:is_display type:str require:0  other: desc:0不显示1菜单显示
     * @param .name:name type:str require:0  other: desc:搜索关键字
     * @return role:页面信息@
     * @role  name:名称
     * @role  remark:描述
     * @return is_display  remark:是否是菜单
     * @return auths:所有权限ID
     * @return auth_select:已选择权限ID
     * @return rule: 权限列表@
     * @rule list:子权限列表@ name:子权限名称 title:子权限标题
     * @list name:权限名称 title:权限标题
     **/
    public function editRolePage()
    {
        $params = $this->request->param();
        $id = isset($params["id"]) ? intval($params["id"]) : "";
        $is_display = isset($params["display"]) ? intval($params["display"]) : "0,1";
        $name = isset($params["name"]) ? intval($params["name"]) : "";
        if (!$id) {
            return jsonrule(["status" => 400, "msg" => "ID_ERROR", "rule" => $this->rule]);
        }
        if ($id == 1) {
            return jsonrule(["status" => 400, "msg" => "不允许的操作！", "rule" => $this->rule]);
        }
        $data = \think\Db::name("rolepage")->where("id", $id)->find();
        if (!$data) {
            return jsonrule(["status" => 400, "msg" => "不存在的角色！", "rule" => $this->rule]);
        }
        $role = \think\Db::name("rolepage")->field("name,remark,status")->where("id", $id)->find();
        $auth_role = \think\Db::name("authpage_access")
            ->alias("a")
            ->field("b.id,b.pid,b.is_display")
            ->leftJoin("auth_rule b", "a.rule_name=b.name")
            ->where("a.rolepage_id", $id)
            ->where("b.is_display", "in", $is_display)
            ->where("b.title", "like", "%" . $name . "%")
            ->select()
            ->toArray();
        $auths = \think\Db::name("auth_rule")
            ->field("id,pid,is_display,name,title")
            ->where("is_display", "in", $is_display)
            ->where("title", "like", "%" . $name . "%")
            ->select()
            ->toArray();
        $auths_tree = $this->listToTree($auths);
        return jsonrule([
            "status" => 200,
            "msg" => lang("SUCCESS MESSAGE"),
            "role" => $role,
            "auths" => $auths_tree,
            "auth_select" => array_column($auth_role, "id") ?: [],
        ]);
    }
    /**
     * @title 编辑页面（编辑页面）
     * @description 接口说明:编辑页面（编辑页面）
     * @author 刘国栋
     * @url admin/rbac/edit
     * @method POST
     * @param .name:id type:int require:1  other: desc:页面ID
     * @param .name:name type:str require:1  other: desc:名称
     * @param .name:remark type:str require:0  other: desc:描述
     * @param .name:status type:int require:1  other: desc:状态（1：开启，0：禁用）
     * @param .name:auth[] type:int require:0  other: desc:权限ID组
     **/
    public function editRole()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only("id,name,remark,status,auth");
            $id = isset($data["id"]) ? intval($data["id"]) : "";
            if (!$id) {
                return jsonrule(["status" => 400, "msg" => "ID_ERROR"]);
            }
            $auth = array_filter($data["auth"], function ($x) {
                return $x > 0 && is_numeric($x);
            });
            unset($data["auth"]);
            $rule = ["name" => "require|max:15", "remark" => "max:255", "status" => "require|in:0,1"];
            $msg = ["name.require" => "名称不能为空", "status.require" => "状态不能为空"];
            $validate = new \think\Validate($rule, $msg);
            $validate_result = $validate->check($data);
            if (!$validate_result) {
                return jsonrule(["status" => 400, "msg" => $validate->getError()]);
            }
            if (!empty($auth) && is_array($auth)) {
                $auth = \think\Db::name("auth_rule")->whereIn("id", $auth)->select()->toArray();
                $auth = array_column($auth, "name");
            }
            $dec = "";
            $roles = db("rolepage")->field("name,remark,status")->where("id", $id)->find();
            if ($data["name"] != $roles["name"]) {
                $dec .= " - 权限组名" . $roles["name"] . "改为" . $data["name"];
            }
            if ($data["remark"] != $roles["remark"]) {
                $dec .= " - 权限组描述" . $roles["remark"] . "改为" . $data["remark"];
            }
            if ($data["status"] != $roles["status"]) {
                if ($roles["status"] == 1) {
                    $dec .= " - 禁用";
                } else {
                    $dec .= " - 启用";
                }
            }
            $data["update_time"] = time();
            \think\Db::startTrans();
            try {
                db("rolepage")->where("id", $id)->update($data);
                $insert = [];
                foreach ($auth as $v) {
                    $insert[] = ["role_id" => $id, "rule_name" => $v, "type" => "admin_url"];
                }
                \think\Db::name("authpage_access")->where("role_id", $id)->delete();
                if (!empty($insert)) {
                    \think\Db::name("authpage_access")->insertAll($insert);
                }
                \think\Db::commit();
                active_log(sprintf($this->lang["Rabcpage_admin_editRole"], $id, $dec));
                unset($dec);
            } catch (\Exception $e) {
                \think\Db::rollback();
                return jsonrule(["status" => 400, "msg" => lang("UPDATE FAIL")]);
            }
            return jsonrule(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
        }
        return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
    }
    /**
     * @title 删除角色(删除管理员组)
     * @description 接口说明：删除角色(删除管理员组)
     * @author 上官🔪
     * @url admin/rbac/:id/
     * @method delete
     * @param .name:id type:int require:1  other: desc:管理员组id
     **/
    public function delete()
    {
        $id = $this->request->param("id", 0, "intval");
        if ($id == 1) {
            return jsonrule(["status" => 400, "msg" => lang("IMPOSSIBILITY DELETE")]);
        }
        $count = \think\Db::name("RoleUser")->where("role_id", $id)->count();
        if ($count > 0) {
            return jsonrule(["status" => 400, "msg" => lang("EXIST_AMDIN")]);
        } else {
            $status = \think\Db::name("role")->delete($id);
            if (!empty($status)) {
                active_log(sprintf($this->lang["Rabcpage_admin_deleteRole"], $id));
                return jsonrule(["status" => 204, "msg" => lang("DELETE SUCCESS")]);
            } else {
                return jsonrule(["status" => 400, "msg" => lang("DELETE FAIL")]);
            }
        }
    }
    /**
     * @title 将数组转换成树形结构
     * @description 处理数组
     * @author 刘国栋
     * @method listToTree
     * @param $list:数组 $pk:子ID $pid:父类ID  $display: 菜单字段 $child:子集菜单名 $root 一级父id
     **/
    private function listToTree(
        $list,
        $pk = "id",
        $pid = "pid",
        $display = "is_display",
        $child = "sublevel",
        $root = 0
    ) {
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][$data[$pk]] = &$list[$key];
                        $parent[$child] = array_values($parent[$child]);
                    }
                }
            }
        }
        return $tree;
    }
}
