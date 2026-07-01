<?php

namespace app\admin\controller;

/**
 * @title 账单项目管理
 * @group 后台账单管理
 */
class InvoiceItemsController extends AdminBaseController
{
    public function index() {}
    /**
     * 显示创建资源表单页.
     * @return \think\Response
     */
    public function create() {}
    /**
	* @title 添加账单项目
	* @description 接口说明:
	* @param .name:id type:int require:0  other: desc:
	* @param .name:uid type:int require:0  other: desc:
	* @param .name:description type:string require:0  other: desc:描述
	* @param .name:amount type:string require:0  other: desc:金额
	//     * @param .name:taxed type:string require:0  other: desc:账单状态(Pending,Active,Completed,Suspend,Terminated,Cancelled,Fraud)
	* @return
	* @throws
	* @author 上官🔪
	* @url /admin/orders
	* @method get
	*/
    public function save(\think\Request $request)
    {
        $param = $request->only("id,uid,description,amount,taxed");
        $validate = new \app\admin\validate\InvoiceItemValidate();
        if (!$validate->check($param)) {
            return jsonrule($validate->getError(), 400);
        }
        try {
            db("invoice_items")->insert($param);
            return jsonrule(["status" => 200, "msg" => "ok"]);
        } catch (\Punic\Exception $e) {
            return jsonrule($e->getError(), 400);
        }
    }
    /**
     * @title 账单项目列表
     * @description 接口说明:
     * @param .name:id type:int require:0  other: desc:
     * @param .name:uid type:int require:0  other: desc:
     * @param .name:description type:string require:0  other: desc:描述
     * @param .name:amount type:string require:0  other: desc:金额
     * @return
     * @throws
     * @author 上官🔪
     * @url /admin/invoice_items
     * @method get
     */
    public function read($id)
    {
        $param = $this->request->param();
        $order = isset($param["order"][0]) ? trim($param["order"]) : "id";
        $sort = isset($param["sort"][0]) ? trim($param["sort"]) : "DESC";
        $where["invoice_id"] = $id;
        $where["delete_time"] = null;
        $rows = db("invoice_items")
            ->field("id,invoice_id,description,amount,taxed")
            ->order($order, $sort)
            ->where($where)
            ->select();
        return jsonrule(["data" => $rows, "status" => 200, "msg" => "ok"]);
    }
    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id) {}
    /**
     * @title 账单项目批量更新
     * @description 接口说明:
     * @url /admin/invoice_items
     * @param .name:id type:dict require:1  other: desc:账单id
     * @param .name:data type:dict require:0  other: desc:{data:{id:int,description:string,amount:float}}
     * @return
     * @throws
     * @author 上官🔪
     * @method put
     */
    public function update(\think\Request $request, $id)
    {
        $data = \request()->put("data");
        $items = [];
        foreach ($data as $k => $v) {
            $item = model("invoice_items")->where("id", $v["id"])->field("id,invoice_id,description,amount")->find();
            if ($id == $item["invoice_id"]) {
                $item->amount = $v["amount"];
                $item->description = $v["description"];
                $item->save();
                $items[] = $item;
            } else {
                return jsonrule(["status" => 400, "msg" => "项目与账单不匹配"]);
            }
        }
        return jsonrule(["data" => $items, "status" => 200, "msg" => "ok"]);
    }
    /**
     * @title 账单项目删除
     * @description 接口说明:
     * @param .name:id type:dict require:0  other:账单项目id
     * @return
     * @throws
     * @author 上官🔪
     * @url /admin/invoice_items
     * @method delete
     */
    public function delete($id)
    {
        $rows = db("invoice_items")->delete($id);
        return jsonrule(["data" => $rows, "status" => 200, "msg" => "ok"]);
    }
}
