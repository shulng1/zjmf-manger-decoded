<?php

namespace app\home\controller;

use app\Service\Cache\CacheService;

/**
 * @title 前台用户
 * @description 接口说明
 */
class UserController extends CommonController
{
    private function secondVerify()
    {
        $action = request()->action();
        if ($action == "modifypassword") {
            $action = "modify_password";
        } elseif ($action == "getapipwd") {
            $action = "get_api_pwd";
        } elseif ($action == "modifyapipwd") {
            $action = "modify_api_pwd";
        }
        return secondVerifyResultHome($action);
    }
    /**
	* @title 基本信息
	* @description 接口说明
	* @return .username:用户名
	* @return .usertype:用户类型
	* @return .sex:性别
	* @return .avatar:头像
	* @return .profession:职业
	* @return .signature:个性签名
	* @return .companyname:所在公司
	* @return .email:邮件   邮箱有则绑定
	* @return .wechat_id:微信id   大于0有则绑定
	* @return .country:国家
	* @return .province:省份
	* @return .city:城市
	* @return .region:区
	* @return .address1:具体地址1
	* @return .postcode:邮编
	* @return .phonenumber:电话 有则绑定手机
	* @return .tax_id:税号ID
	* @return .authmodule:授权模块
	* @return .authdata:授权数据
	* @return .currency:使用货币ID
	* @return .defaultgateway:选择默认支付接口
	* @return .credit:信用卡
	* @return .taxexempt:免税（1：是:0：否）
	* @return .latefeeoveride:滞纳金覆盖（1：是；0：否）
	* @return .overideduenotices:覆盖过期notices（是，否）
	* @return .separateinvoices:单独发票（1：是；0：否）
	* @return .disableautocc:禁用自动CC处理（是，否）
	* @return .datecreated:创建日期
	* @return .notes:备注
	* @return .billingcid:付款联系人（子账户）ID
	* @return .groupid:用户组ID
	* @return .cardlastfour:信用卡后四位
	* @return .cardnum:信用卡号
	* @return .lastlogin:最后登录时间
	* @return .host:主机
	* @return .status:状态（1激活，0未激活，2关闭）
	* @return .language:语言
	* @return .marketing_emails_opt_in:发送客户营销邮件（1：是；0：否）
	* @return .create_time:创建时间
	* @return .update_time:更新时间
	* @return .pwresetexpiry:密码重置过期时间
	* @return .know_us:了解途径
	* @return .initiative_renew:是否使用余额自动续费(1使用,0不使用)
	* @return .is_login_sms_reminder:是否开启登录短信提醒(1开启,0不开启)
	* @return .email_remind:是否开启登录邮件提醒(1开启默认,0不开启)
	* @return .certifi.status 个人认证信息状态和失败原因(1已认证，2未通过，3待审核，4已提交资料)0=为认证
	* @return .certifi.type 认证类型certifi_pseson个人认证，certifi_company企业认证
	* @return .certifi.auth_fail 失败原因
	* @return .is_password 是否设置密码1=设置 0=未设置
	* @return .second_verify: 是否开启二次验证：0否默认，1是
	* @return allow_resource_api:是否开启api设置菜单
	* @return allow_second_verify:是否开启二次验证设置菜单
	* @return second_verify_action_home:需要二次验证的动作,数组(['name'=>'on','name_zh'=>'开机'],
	* @return cart_product_description:购物车页面 应用说明
	* @return shd_allow_sms_send:短信设置
	* @return shd_allow_email_send:邮件设置
	    ['name'=>'off','name_zh'=>'关机'],
	    ['name'=>'reboot','name_zh'=>'重启'],
	    ['name'=>'hardOff','name_zh'=>'硬关机'],
	    ['name'=>'hardReboot','name_zh'=>'硬重启'],
	    ['name'=>'crackPass','name_zh'=>'重置密码'],
	    ['name'=>'rescue','name_zh'=>'救援系统'],
	    ['name'=>'vnc','name_zh'=>'控制台'],
	    ['name'=>'login','name_zh'=>'登录'],
	    ['name'=>'modify_password','name_zh'=>'修改密码'],
	    ['name'=>'closed','name_zh'=>'关闭二次验证'],)
	* @throws
	* @author 上官🔪
	* @url /user_info
	* @method GET
	*/
    public function index(\think\Request $request)
    {
        $id = $request->uid;
        $userinfo = db("clients")
            ->field(
                "id,username,usertype,sex,profession,signature,companyname,groupid,email,wechat_id,country,province,city,region,address1,postcode,phonenumber,currency,defaultgateway,credit,billingcid,cardtype,cardlastfour,host,language,emailoptout,marketing_emails_opt_in,overrideautoclose,allow_sso,know_us,is_login_sms_reminder,password,create_time,sale_id,qq,api_password,second_verify,status,email_remind,is_open_credit_limit,api_open,send_close",
            )
            ->where("id", $id)
            ->find();
        $userinfo["defaultgateway"] = getGateway($id);
        $userinfo["is_password"] = isset($userinfo["password"][1]) ? 1 : 0;
        unset($userinfo["password"]);
        $userinfo["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $userinfo["is_open_credit_limit"] : 0;
        if (isset($userinfo["wechat_id"]) && !empty($userinfo["wechat_id"])) {
            $wechat_info = db("wechat_user")->where("id", $userinfo["wechat_id"])->find();
            if (!$wechat_info) {
                $data = ["status" => 400, "msg" => "获取微信信息失败"];
                return json($data);
            }
            $userinfo["username"] = $wechat_info["nickname"];
            $userinfo["sex"] = $wechat_info["sex"];
            $userinfo["country"] = $wechat_info["country"];
            $userinfo["province"] = $wechat_info["province"];
            $userinfo["city"] = $wechat_info["city"];
        }
        $certifi_company = \think\Db::name("certifi_company")->where("auth_user_id", $id)->find();
        if (!empty($certifi_company) && $certifi_company["status"] == 1) {
            $userinfo["certifi"] = [
                "name" => $certifi_company["auth_real_name"] ?? "",
                "status" => $certifi_company["status"] ?? "",
                "auth_fail" => $certifi_company["auth_fail"] ?? "",
                "type" => "certifi_company",
            ];
        } else {
            $certifi_person = \think\Db::name("certifi_person")->where("auth_user_id", $id)->find();
            if (!empty($certifi_person) && $certifi_person["status"] == 1) {
                $userinfo["certifi"] = [
                    "name" => $certifi_person["auth_real_name"] ?? "",
                    "status" => $certifi_person["status"] ?? "",
                    "auth_fail" => $certifi_person["auth_fail"] ?? "",
                    "type" => "certifi_person",
                ];
            } else {
                $userinfo["certifi"] = ["name" => "", "status" => 2, "auth_fail" => "", "type" => "certifi_person"];
            }
        }
        $userinfo["api_password"] = $userinfo["api_password"]
            ? htmlspecialchars_decode(aesPasswordDecode($userinfo["api_password"]))
            : "";
        $client_customs_value = (new \app\common\model\CustomfieldsModel())->getCustomFieldValue($id);
        $group = \think\Db::name("client_groups")
            ->field("group_name,group_colour")
            ->where("id", $userinfo["groupid"])
            ->find();
        $developer = \think\Db::name("developer")->where("uid", $id)->order("id", "desc")->find();
        $allow_resource_api = 0;
        if (configuration("allow_resource_api") && $userinfo["api_open"] == 1) {
            $allow_resource_api = 1;
        }
        $data = [
            "allow_resource_api" => judgeApi($id),
            "allow_second_verify" => intval(configuration("second_verify_home")),
            "user" => $userinfo,
            "certifi_open" => configuration("certifi_open") ?? 1,
            "customs" => $client_customs_value,
            "gateways" => hook("get_client_only_payment", ["uid" => $id]) ?: gateway_list1(),
            "client_group" => $group,
            "developer" => $developer ?? [],
            "status" => 200,
            "msg" => lang("SUCCESS MESSAGE"),
            "second_verify_action_home" => explode(",", configuration("second_verify_action_home")),
            "voucher_manager" => configuration("voucher_manager") ?? 0,
            "buy_product_must_bind_phone" => buyProductMustBindPhone($id) ? 0 : 1,
            "shd_allow_sms_send" => configuration("shd_allow_sms_send"),
            "shd_allow_email_send" => configuration("shd_allow_email_send"),
        ];
        $allow_resource_api_realname = configuration("allow_resource_api_realname") ?? 0;
        $allow_resource_api_phone = configuration("allow_resource_api_phone") ?? 0;
        $phone_verify = $userinfo["phonenumber"] ? 1 : 0;
        if ($data["allow_resource_api"]) {
            $realname = 1;
            $_phone = 1;
            if ($allow_resource_api_realname) {
                $realname = $userinfo["certifi"]["status"] == 1 ? 1 : 0;
            }
            if ($allow_resource_api_phone) {
                $_phone = $phone_verify;
            }
            $data["allow_resource_api"] = $realname && $_phone ? $data["allow_resource_api"] : 0;
        }
        return jsons($data);
    }
    /**
     * @title 二次验证切换开关
     * @description 接口说明:二次验证切换开关,单独处理二次验证
     * @author wyh
     * @url /toggle_second_verify
     * @method POST
     * @param .name:second_verify type:tinyint require:0 default:0 other:0关闭，1开启
     * @param .name:code type:string require:0 default:0 other:验证码
     * @param .name:type type:string require:0 default:0 other:发送验证方式,email,phone
     */
    public function toggleSecondVerify()
    {
        $param = $this->request->param();
        $second_verify = intval($param["second_verify"]);
        if (!in_array($second_verify, [0, 1])) {
            return jsons(["status" => 400, "msg" => "参数错误"]);
        }
        if (!configuration("second_verify_home")) {
            return jsons(["status" => 400, "msg" => "未开启二次验证功能"]);
        }
        $uid = request()->uid;
        $client = \think\Db::name("clients")->field("phonenumber,email,second_verify")->where("id", $uid)->find();
        if (empty($client)) {
            return jsons(["status" => 400, "msg" => "非法操作"]);
        }
        if ($client["second_verify"] == 0 && $second_verify == 0) {
            return jsons(["status" => 400, "msg" => "不可重复操作"]);
        }
        if ($client["second_verify"] == 1 && $second_verify == 1) {
            return jsons(["status" => 400, "msg" => "不可重复操作"]);
        }
        if (empty($client["phonenumber"]) && empty($client["email"]) && $second_verify == 1) {
            return jsons(["status" => 400, "msg" => "未绑定手机或邮箱,无法开启二次验证"]);
        }
        if ($second_verify == 0 && in_array("closed", explode(",", configuration("second_verify_action_home")))) {
            $code = $param["code"] ? trim($param["code"]) : "";
            $type = $param["type"] ? trim($param["type"]) : "";
            if (empty($code)) {
                return jsons(["status" => 400, "msg" => "请输入验证码"]);
            }
            if (!in_array($type, explode(",", configuration("second_verify_action_home_type")))) {
                return jsons(["status" => 400, "msg" => "发送方式错误"]);
            }
            $action = "closed";
            $mobile = $client["phonenumber"];
            $email = $client["email"];
            if ($code != cache($action . "_" . $mobile) && $code != cache($action . "_" . $email)) {
                return jsons(["status" => 400, "msg" => "验证码错误"]);
            } else {
                cache($action . "_" . $mobile, null);
                cache($action . "_" . $email, null);
            }
        }
        $up = \think\Db::name("clients")
            ->where("id", $uid)
            ->update(["second_verify" => $second_verify, "update_time" => time()]);
        if ($up) {
            return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
        } else {
            return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
        }
    }
    /**
     * @title 二次验证页面
     * @description 接口说明:二次验证页面
     * @author wyh
     * @url /second_verify_page
     * @method GET
     */
    public function getSecondVerifyPage()
    {
        $uid = request()->uid;
        $type = explode(",", configuration("second_verify_action_home_type"));
        $all_type = config("second_verify_action_home_type");
        $client = \think\Db::name("clients")->field("phone_code,phonenumber,email")->where("id", $uid)->find();
        $allow_type = [];
        foreach ($all_type as $v) {
            foreach ($type as $vv) {
                if ($vv == $v["name"]) {
                    if ($v["name"] == "email") {
                        $v["account"] = !empty($client["email"])
                            ? str_replace(substr($client["email"], 3, 4), "****", $client["email"])
                            : "未绑定邮箱";
                    } elseif ($v["name"] == "phone") {
                        $v["account"] = !empty($client["phonenumber"])
                            ? str_replace(substr($client["phonenumber"], 3, 4), "****", $client["phonenumber"])
                            : "未绑定手机";
                    }
                    $allow_type[] = $v;
                }
            }
        }
        $data = ["allow_type" => $allow_type];
        return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
    }
    /**
     * @title 二次验证发送验证码
     * @description 接口说明:二次验证发送验证码,所有二次验证都调用此方法
     * @author wyh
     * @url /second_verify_send
     * @method POST
     * @param .name:type type:string require:1 default:0 other:发送方式email,phone
     * @param .name:action type:string require:1 default:0 other:发送动作(closed关闭二次验证)
     */
    public function secondVerifySend()
    {
        $params = $this->request->param();
        $type = $params["type"] ? trim($params["type"]) : "";
        $allow_type = explode(",", configuration("second_verify_action_home_type"));
        if (!in_array($type, $allow_type)) {
            return jsons(["status" => 400, "msg" => "发送方式错误"]);
        }
        $action = $params["action"] ? trim($params["action"]) : "";
        if (!in_array($action, array_column(config("second_verify_action_home"), "name"))) {
            return jsons(["status" => 400, "msg" => "非法操作"]);
        }
        $uid = request()->uid;
        $client = \think\Db::name("clients")->field("id,phone_code,phonenumber,email")->where("id", $uid)->find();
        $code = mt_rand(100000, 999999);
        if ($type == "phone") {
            if (empty($client["phonenumber"])) {
                return jsons(["status" => 400, "msg" => "短信发送失败"]);
            }
            $agent = $this->request->header("user-agent");
            if (strpos($agent, "Mozilla") === false) {
                return jsons(["status" => 400, "msg" => "短信发送失败"]);
            }
            $phone_code = trim($client["phone_code"]);
            $mobile = trim($client["phonenumber"]);
            $rangeTypeCheck = rangeTypeCheck($phone_code . $mobile);
            if ($rangeTypeCheck["status"] == 400) {
                return jsonrule($rangeTypeCheck);
            }
            if (CacheService::has($action . "_" . $mobile . "_time")) {
                return jsons(["status" => 400, "msg" => lang("CODE_SENDED")]);
            }
            if ($phone_code == "+86" || $phone_code == "86" || empty($phone_code)) {
                $phone = $mobile;
            } else {
                if (substr($phone_code, 0, 1) == "+") {
                    $phone = substr($phone_code, 1) . "-" . $mobile;
                } else {
                    $phone = $phone_code . "-" . $mobile;
                }
            }
            $params = ["code" => $code];
            $sms = new \app\common\logic\Sms();
            $result = $sms->sendSms(8, $phone, $params, false, $client["id"]);
            if ($result["status"] == 200) {
                cache($action . "_" . $mobile, $code, 300);
                CacheService::set($action . "_" . $mobile . "_time", $code, 60);
                return jsons(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
            } else {
                return jsons(["status" => 400, "msg" => lang("CODE_SEND_FAIL")]);
            }
        } elseif ($type == "email") {
            if (configuration("shd_allow_email_send") == 0) {
                return jsonrule(["status" => 400, "msg" => "邮箱发送功能已关闭"]);
            }
            if (empty($client["email"])) {
                return jsons(["status" => 400, "msg" => "发送失败"]);
            }
            $email = $client["email"];
            if (!CacheService::has($action . "_" . $email . "_time")) {
                $email_logic = new \app\common\logic\Email();
                $result = $email_logic->sendEmailCode($email, $code);
                if ($result) {
                    cache($action . "_" . $email, $code, 300);
                    CacheService::set($action . "_" . $email . "_time", $code, 60);
                    return jsons(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
                } else {
                    return jsons(["status" => 400, "msg" => lang("CODE_SEND_FAIL")]);
                }
            } else {
                return jsons(["status" => 400, "msg" => lang("CODE_SENDED")]);
            }
        } else {
            return jsons(["status" => 400, "msg" => "发送失败"]);
        }
    }
    /**
     * @title 修改api秘钥页面
     * @description 接口说明:修改api秘钥页面
     * @author wyh
     * @url /get_api_pwd
     * @method GET
     */
    public function getApiPwd()
    {
        if (!judgeApiIs()) {
            return jsons(["status" => 400, "msg" => "未开启API设置"]);
        }
        $res = $this->secondVerify();
        if ($res["status"] != 200) {
            return jsons($res);
        }
        $uid = request()->uid;
        $user = \think\Db::name("clients")->where("id", intval($uid))->find();
        $api_password = $user["api_password"] ? htmlspecialchars_decode(aesPasswordDecode($user["api_password"])) : "";
        return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $api_password]);
    }
    /**
     * @title 修改api秘钥
     * @description 接口说明:修改api秘钥
     * @author wyh
     * @url /modify_api_pwd
     * @method POST
     * @param .name:api_password type:string require:0 default:0 other:api秘钥
     */
    public function modifyApiPwd()
    {
        if (!judgeApiIs()) {
            return jsons(["status" => 400, "msg" => "未开启API设置"]);
        }
        $res = $this->secondVerify();
        if ($res["status"] != 200) {
            return jsons($res);
        }
        $params = $this->request->param();
        $api_password = $params["api_password"] ?? "";
        if (empty($api_password)) {
            return json(["status" => 400, "msg" => "密钥不能为空"]);
        }
        if (preg_match("/[\\x{4e00}-\\x{9fa5}]+/u", $api_password)) {
            return json(["status" => 400, "msg" => "密钥不能包含中文"]);
        }
        if (!preg_match("/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\W_]).{8,32}/", $api_password)) {
            return json(["status" => 400, "msg" => "密钥由大小写字母、数字、特殊字符组成"]);
        }
        $uid = request()->uid;
        $up = ["api_password" => aesPasswordEncode($api_password)];
        \think\Db::name("clients")->where("id", $uid)->update($up);
        return jsons(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
    }
    /**
     * @title 随机生成api秘钥
     * @description 接口说明:随机生成api秘钥
     * @author wyh
     * @url /auto_api_pwd
     * @method GET
     */
    public function autoApiPwd()
    {
        $data = ["api_password" => randStrToPass(12, 0)];
        return jsons(["status" => 200, "msg" => lang("UPDATE SUCCESS"), "data" => $data]);
    }
    /**
     * @title 修改用户资料
     * @description 接口说明:至少一个参数
     * @param .name:username type:string require:1 default:1 other: desc:用户名
     * @param .name:sex type:int require:1 default:1 other: desc:性别（0未知，1男，2女）
     * @param .name:avatar type:string require:0 default:1 other: desc:头像
     * @param .name:profession type:string require:0 default:1 other: desc:职业
     * @param .name:signature type:string require:0 default:1 other: desc:个性签名
     * @param .name:companyname type:string require:0 default:1 other: desc:所在公司
     * @param .name:email type:string require:0 default:0 other: desc:邮件
     * @param .name:country type:string require:0 default:0 other: desc:国家
     * @param .name:province type:string require:0 default:0 other: desc:省份
     * @param .name:city type:string require:0 default:0 other: desc:城市
     * @param .name:region type:string require:0 default:0 other: desc:区
     * @param .name:address1 type:string require:0 default:1 other: desc:具体地址1
     * @param .name:postcode type:string require:0 default:1 other: desc:邮编
     * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
     * @param .name:phonenumber type:string require:0 default:1 other: desc:电话
     * @param .name:currency type:int require:1 default:1 other: desc:使用货币ID
     * @param .name:defaultgateway type:string require:1 default:1 other: desc:选择默认支付接口
     * @param .name:notes type:string require:0 default:0 other: desc:管理员备注
     * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
     * @param .name:status type:int require:0 default:0 other: desc:状态（0未激活，1激活，2关闭）
     * @param .name:language type:string require:1 default:0 other: desc:语言(传zh_cn/zh_xg/en_us等)
     * @param .name:know_us type:string require:0 default:0 other: desc:了解途径
     * @param .name:custom[id] type:string require:1 default:0 other: desc:自定义字段值.形式：custom[id] = value;此参数必传，没有值传custom[];
     * @return .username:用户名
     * @return .usertype:用户类型
     * @return .sex:性别
     * @return .avatar:头像
     * @return .profession:职业
     * @return .signature:个性签名
     * @return .companyname:所在公司
     * @return .email:邮件
     * @return .country:国家
     * @return .province:省份
     * @return .city:城市
     * @return .region:区
     * @return .address1:具体地址1
     * @return .postcode:邮编
     * @return .phonenumber:电话
     * @return .tax_id:税号ID
     * @return .authmodule:授权模块
     * @return .authdata:授权数据
     * @return .currency:使用货币ID
     * @return .defaultgateway:选择默认支付接口
     * @return .credit:信用卡
     * @return .taxexempt:免税（1：是:0：否）
     * @return .latefeeoveride:滞纳金覆盖（1：是；0：否）
     * @return .overideduenotices:覆盖过期notices（是，否）
     * @return .separateinvoices:单独发票（1：是；0：否）
     * @return .disableautocc:禁用自动CC处理（是，否）
     * @return .datecreated:创建日期
     * @return .notes:备注
     * @return .billingcid:付款联系人（子账户）ID
     * @return .groupid:用户组ID
     * @return .cardlastfour:信用卡后四位
     * @return .cardnum:信用卡号
     * @return .lastlogin:最后登录时间
     * @return .host:主机
     * @return .status:状态（1激活，0未激活，2关闭）
     * @return .language:语言
     * @return .marketing_emails_opt_in:发送客户营销邮件（1：是；0：否）
     * @return .create_time:创建时间
     * @return .update_time:更新时间
     * @return .pwresetexpiry:密码重置过期时间
     * @return .know_us:了解途径
     * @throws
     * @author 上官🔪
     * @url /user_info
     * @method PUT
     */
    public function update(\think\Request $request)
    {
        $id = $request->uid;
        $data = $params = $this->request->only([
            "username",
            "sex",
            "avatar",
            "profession",
            "signature",
            "companyname",
            "country",
            "province",
            "city",
            "region",
            "address1",
            "postcode",
            "currency",
            "defaultgateway",
            "notes",
            "language",
            "know_us",
            "custom",
            "qq",
            "marketing_emails_opt_in",
            "send_close",
        ]);
        if (empty($data["marketing_emails_opt_in"])) {
            $data["marketing_emails_opt_in"] = 0;
        }
        if (empty($data["send_close"])) {
            $data["send_close"] = 0;
        }
        $validate = new \app\home\validate\UserValidate();
        if (true !== $validate->remove("country", "require")->check($data)) {
            return json(["status" => 400, "msg" => $validate->getError()]);
        }
        unset($data["__token__"]);
        unset($data["_method"]);
        if (isset($data["avatar"][0])) {
            $upload = new \app\common\logic\Upload();
            $avatar = $upload->moveTo($data["avatar"], config("client_avatar"));
            if (isset($avatar["error"])) {
                return json(["status" => 400, "msg" => $avatar["error"]]);
            }
        }
        if (isset($data["custom"]) && !empty($data["custom"])) {
            $customs = $data["custom"];
            unset($data["custom"]);
        }
        $data["status"] = 1;
        $data["update_time"] = time();
        $currency_id = \think\Db::name("currencies")->where("default", 1)->value("id");
        if (empty($data["currency"])) {
            $data["currency"] = $currency_id;
        }
        $resultuid = db("clients")->where("id", $id)->find();
        $dec = "";
        if (!empty($params["username"]) && $params["username"] != $resultuid["username"]) {
            $dec .= "  客户名: " . $resultuid["username"] . "改为" . $params["username"] . " - ";
        }
        if (!empty($params["password"]) && $params["password"] != $resultuid["password"]) {
            $dec .= "  密码: " . $resultuid["password"] . "改为" . $params["password"] . " - ";
        }
        if (!empty($params["sex"]) && $params["sex"] != $resultuid["sex"]) {
            $dec .= "  性别: " . $resultuid["sex"] . "改为" . $params["sex"] . " - ";
        }
        if (!empty($params["qq"]) && $params["qq"] != $resultuid["qq"]) {
            $dec .= "  qq: " . $resultuid["qq"] . "改为" . $params["qq"] . " - ";
        }
        if (!empty($params["avatar"]) && $params["avatar"] != $resultuid["avatar"]) {
            $dec .= "  头像: " . $resultuid["avatar"] . "改为" . $params["avatar"] . " - ";
        }
        if (!empty($params["profession"]) && $params["profession"] != $resultuid["profession"]) {
            $dec .= "  职业: " . $resultuid["profession"] . "改为" . $params["profession"] . " - ";
        }
        if (!empty($params["signature"]) && $params["signature"] != $resultuid["signature"]) {
            $dec .= "  个性签名: " . $resultuid["signature"] . "改为" . $params["signature"] . " - ";
        }
        if (!empty($params["companyname"]) && $params["companyname"] != $resultuid["companyname"]) {
            $dec .= "  所在公司: " . $resultuid["companyname"] . "改为" . $params["companyname"] . " - ";
        }
        if (!empty($params["email"]) && $params["email"] != $resultuid["email"]) {
            $dec .= "  邮件: " . $resultuid["email"] . "改为" . $params["email"] . " - ";
        }
        if (!empty($params["country"]) && $params["country"] != $resultuid["country"]) {
            $dec .= "  国家: " . $resultuid["country"] . "改为" . $params["country"] . " - ";
        }
        if (!empty($params["province"]) && $params["province"] != $resultuid["province"]) {
            $dec .= "  省份: " . $resultuid["province"] . "改为" . $params["province"] . " - ";
        }
        if (!empty($params["city"]) && $params["city"] != $resultuid["city"]) {
            $dec .= "  城市: " . $resultuid["city"] . "改为" . $params["city"] . " - ";
        }
        if (!empty($params["region"]) && $params["region"] != $resultuid["region"]) {
            $dec .= "  区: " . $resultuid["region"] . "改为" . $params["region"] . " - ";
        }
        if (!empty($params["address1"]) && $params["address1"] != $resultuid["address1"]) {
            $dec .= "  具体地址1: " . $resultuid["address1"] . "改为" . $params["address1"] . " - ";
        }
        if (!empty($params["address2"]) && $params["address2"] != $resultuid["address2"]) {
            $dec .= "  具体地址2: " . $resultuid["address2"] . "改为" . $params["address2"] . " - ";
        }
        if (!empty($params["postcode"]) && $params["postcode"] != $resultuid["postcode"]) {
            $dec .= "  邮编: " . $resultuid["postcode"] . "改为" . $params["postcode"] . " - ";
        }
        if (!empty($params["phone_code"]) && $params["phone_code"] != $resultuid["phone_code"]) {
            $dec .= "  国际电话区号: " . $resultuid["phone_code"] . "改为" . $params["phone_code"] . " - ";
        }
        if (!empty($params["phonenumber"]) && $params["phonenumber"] != $resultuid["phonenumber"]) {
            $dec .= "  电话: " . $resultuid["phonenumber"] . "改为" . $params["phonenumber"] . " - ";
        }
        if (!empty($params["defaultgateway"]) && $params["defaultgateway"] != $resultuid["defaultgateway"]) {
            $arr = gateway_list();
            $arr = array_column($arr, "title", "name");
            $dec .=
                "  选择默认支付接口: " . $arr[$resultuid["defaultgateway"]] . "改为" . $arr[$params["defaultgateway"]];
        }
        if (!empty($params["notes"]) && $params["notes"] != $resultuid["notes"]) {
            $dec .= "  备注: " . $resultuid["notes"] . "改为" . $params["notes"] . " - ";
        }
        if (!empty($params["groupid"]) && $params["groupid"] != $resultuid["groupid"]) {
            $dec .= "  客户分组Group ID:" . $resultuid["groupid"] . "改为" . $params["groupid"] . " - ";
        }
        if (!empty($params["status"]) && $params["status"] != $resultuid["status"]) {
            $dec .= "  状态: " . $resultuid["status"] . "改为" . $params["status"] . " - ";
        }
        if (!empty($params["language"]) && $params["language"] != $resultuid["language"]) {
            $dec .= "  语言: " . $resultuid["language"] . "改为" . $params["language"] . " - ";
        }
        if (!empty($params["know_us"]) && $params["know_us"] != $resultuid["know_us"]) {
            $dec .= "  了解途径: " . $resultuid["know_us"] . "改为" . $params["know_us"] . " - ";
        }
        $custom_model = new \app\common\model\CustomfieldsModel();
        $client_customs = \think\Db::name("customfields")
            ->field("id,fieldname,fieldtype,fieldoptions,required,regexpr")
            ->where("type", "client")
            ->where("adminonly", 0)
            ->select()
            ->toArray();
        $res = $custom_model->check($client_customs, $customs);
        if ($res["status"] == "error") {
            return json(["status" => 400, "msg" => $res["msg"]]);
        }
        $flag = true;
        \think\Db::startTrans();
        try {
            db("clients")->where("id", $id)->update($data);
            $custom_model->updateCustomValue(0, $id, $customs, "client");
            \think\Db::commit();
        } catch (\Exception $e) {
            \think\Db::rollback();
            $flag = false;
        }
        if ($flag) {
            active_logs(sprintf($this->lang["User_home_update_userinfo_success"], $id, $dec), $id);
            active_logs(sprintf($this->lang["User_home_update_userinfo_success_home"], $id), $id, "", 2);
            unset($dec);
            return jsons(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
        }
        return jsons(["status" => 400, "msg" => lang("UPDATE FAIL")]);
    }
    /**
     * @title 绑定手机:发送验证码 --页面
     * @description 接口说明: 绑定手机:发送验证码 --页面
     * @author wyh
     * @url check_origin_phone
     * @method GET
     */
    public function checkOriginPhone()
    {
        $data = [];
        $uid = request()->uid;
        $phone = \think\Db::name("clients")
            ->field("phone_code,phonenumber,email")
            ->where("id", $uid)
            ->where("status", 1)
            ->find();
        $data["tel"] = $phone;
        $data["country_code"] = getCountryCode();
        return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
    }
    /**
     * @title 绑定手机:发送验证码
     * @description 接口说明: 绑定手机:发送验证码
     * @param .name:phone_code type:string require:1 default:1 other: desc:国际手机区号
     * @param .name:phone type:string require:1 default:1 other: desc:手机号
     * @param .name:mk type:string require:1  other: desc:common_list接口返回的msfntk作为cookie写入,并在发送短信时作为token传入
     * @author wyh
     * @url /bind_phone
     * @method POST
     */
    public function bind_phone_send()
    {
        $agent = $this->request->header("user-agent");
        if (strpos($agent, "Mozilla") === false) {
            return json(["status" => 400, "msg" => "短信发送失败1"]);
        }
        if ($this->request->isPost()) {
            $validate = new \think\Validate(["phone" => "require"]);
            $data = $this->request->param();
            if (
                !captcha_check($data["captcha"], "allow_phone_bind_captcha") &&
                configuration("allow_phone_bind_captcha") == 1 &&
                configuration("is_captcha") == 1
            ) {
                return json(["status" => 400, "msg" => "图形验证码有误"]);
            }
            if (cookie("msfntk") != $data["mk"] || !cookie("msfntk")) {
            }
            if (!$validate->check($data)) {
                return jsons(["status" => 400, "msg" => $validate->getError()]);
            }
            $id = $this->request->uid;
            $clientsModel = new \app\home\model\ClientsModel();
            $res = $clientsModel->where("phonenumber", $data["phone"])->find();
            if (!empty($res)) {
                if ($res["phonenumber"] == $data["phone"]) {
                    return jsons(["status" => 400, "msg" => "该手机号已绑定，无需重复操作"]);
                }
                if ($res["id"] != $id) {
                    return jsons(["status" => 400, "msg" => "绑定失败"]);
                }
            }
            $phone_code = trim($data["phone_code"]);
            $mobile = trim($data["phone"]);
            $rangeTypeCheck = rangeTypeCheck($phone_code . $mobile);
            if ($rangeTypeCheck["status"] == 400) {
                return jsonrule($rangeTypeCheck);
            }
            $prefix = "bind_phone";
            $result = $this->sendPhoneCode($phone_code, $mobile, $prefix, $id);
            return $result;
        }
        return jsons(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
    }
    /**
     * @title 绑定手机
     * @description 接口说明: 绑定手机
     * @param .name:phone_code type:string require:1 default:1 other: desc:国际手机区号
     * @param .name:phone type:string require:1 default:1 other: desc:手机号
     * @param .name:code type:int require:1  other: desc:验证码
     * @author wyh
     * @url bind_phone_handle
     * @method POST
     */
    public function bind_phone_handle()
    {
        $validate = new \think\Validate(["phone_code" => "require", "phone" => "require", "code" => "require"]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            return jsons(["status" => 400, "msg" => $validate->getError()]);
        }
        $mobile = $data["phone"];
        $id = $this->request->uid;
        $clientsModel = new \app\home\model\ClientsModel();
        $res = $clientsModel->where("phonenumber", $data["phone"])->cache("bind_phone", 300)->find();
        if (!empty($res)) {
            if ($res["id"] != $id) {
                return jsons(["status" => 400, "msg" => "该手机号已被他人绑定，请检查"]);
            }
            if ($res["phonenumber"] == $data["phone"]) {
                return jsons(["status" => 400, "msg" => "你已绑定该手机号，无需重复操作"]);
            }
        }
        $code = $data["code"];
        $rel_code = cache("bind_phone" . $mobile);
        if (!isset($rel_code)) {
            return json(["status" => 400, "msg" => "过期的验证"]);
        }
        if ($code != $rel_code) {
            return json(["status" => 400, "msg" => "验证码错误"]);
        }
        $User = \app\home\model\ClientsModel::get($id);
        $where = ["id" => $id];
        $res = $User->save(["phonenumber" => $mobile, "phone_code" => $data["phone_code"]], $where);
        if ($res) {
            CacheService::delete("bind_phone" . $mobile);
            $email_logic = new \app\common\logic\Email();
            $email_logic->sendEmailBind($res["email"] ?? "", "bind phone");
            $message_template_type = array_column(config("message_template_type"), "id", "name");
            $sms = new \app\common\logic\Sms();
            $client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
            if ($client) {
                $params = ["username" => $User["username"], "epw_type" => "手机", "epw_account" => $mobile];
                $ret = sendmsglimit($client["phonenumber"]);
                if ($ret["status"] == 400) {
                    return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
                }
                $ret = $sms->sendSms(
                    $message_template_type[strtolower("email_bond_notice")],
                    $client["phone_code"] . $client["phonenumber"],
                    $params,
                    false,
                    $id,
                );
                if ($ret["status"] == 200) {
                    $data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
                    \think\Db::name("sendmsglimit")->insertGetId($data);
                }
            }
            active_logs(
                sprintf($this->lang["User_home_bind_phone_handle_success"], substr_replace($mobile, "****", 3, 4)),
                $id,
            );
            active_logs(
                sprintf($this->lang["User_home_bind_phone_handle_success"], substr_replace($mobile, "****", 3, 4)),
                $id,
                "",
                2,
            );
            return jsons(["status" => 200, "msg" => "绑定成功！"]);
        }
        return jsons(["status" => 400, "msg" => "绑定失败"]);
    }
    /**
     * @title 更绑手机：发送手机验证码
     * @description 接口说明
     * @param .name:phone_code type:int require:1  other: desc:区号
     * @param .name:tel type:int require:1  other: desc:手机号
     * @param .name:mk type:string require:1  other: desc:common_list接口返回的msfntk作为cookie写入,并在发送短信时作为token传入
     * @param .name:type type:int require:0 default: 1 other: desc:1为原手机验证，2为新手机验证
     * @author 上官🔪
     * @url /bind_phone_code
     * @method get
     */
    public function bind_phone_code(\think\Request $request)
    {
        $agent = $this->request->header("user-agent");
        if (strpos($agent, "Mozilla") === false) {
            return json(["status" => 400, "msg" => "短信发送失败"]);
        }
        $data = $this->request->param();
        if (
            !captcha_check($data["captcha"], "allow_phone_bind_captcha") &&
            configuration("allow_phone_bind_captcha") == 1 &&
            configuration("is_captcha") == 1
        ) {
            return json(["status" => 400, "msg" => "图形验证码有误"]);
        }
        if (cookie("msfntk") != $data["mk"] || !cookie("msfntk")) {
        }
        $id = $request->uid;
        $client = db("clients")->find($id);
        if (isset($data["type"]) && $data["type"] == 2) {
            $validate = new \think\Validate(["tel" => "require|mobile"]);
            if (!$validate->check($data)) {
                return json(["status" => 400, "msg" => $validate->getError()]);
            }
            $type = 2;
        } else {
            $data = ["tel" => $client["phonenumber"], "phone_code" => $client["phone_code"], "code" => $data["code"]];
            $type = 1;
        }
        $phone_code = $data["phone_code"];
        $tel = $data["tel"];
        $rangeTypeCheck = rangeTypeCheck($phone_code . $tel);
        if ($rangeTypeCheck["status"] == 400) {
            return jsonrule($rangeTypeCheck);
        }
        if ($type == 1) {
            if (!isset($client["phonenumber"][0])) {
                return json(["status" => 400, "msg" => "没有绑定手机号"]);
            }
            if ($client["phonenumber"] != $tel) {
                return json(["status" => 400, "msg" => "原手机号吗错误"]);
            }
            $prefix = "ori_phone" . $id . "_";
            $result = $this->sendPhoneCode($phone_code, $tel, $prefix, $id);
            return $result;
        }
        $status = cache("bind_change" . $id . "_status") ?? 0;
        if (!$status) {
            return json(["status" => 400, "msg" => "绑定错误，请重新操作"]);
        }
        if ($client["phonenumber"] == $tel) {
            return json(["status" => 400, "msg" => "手机号没有变化"]);
        }
        $tmp = db("clients")
            ->where([["phonenumber", "=", $tel], ["id", "<>", $id]])
            ->find();
        if (isset($tmp["id"])) {
            return json(["status" => 400, "msg" => "该手机号已被他人绑定，请检查"]);
        }
        $prefix = "new_phone" . $id . "_";
        $result = $this->sendPhoneCode($phone_code, $tel, $prefix, $id);
        return $result;
    }
    private function sendPhoneCode($phone_code, $mobile, $prefix = "", $uid)
    {
        if ($phone_code == "+86" || $phone_code == "86" || empty($phone_code)) {
            $phone = $mobile;
        } else {
            if (substr($phone_code, 0, 1) == "+") {
                $phone = substr($phone_code, 1) . "-" . $mobile;
            } else {
                $phone = $phone_code . "-" . $mobile;
            }
        }
        $code = mt_rand(100000, 999999);
        if (!CacheService::get("bindtime" . $mobile)) {
            $params = ["code" => $code];
            $sms = new \app\common\logic\Sms();
            $ret = sendmsglimit($phone);
            if ($ret["status"] == 400) {
                return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
            }
            $result = $sms->sendSms(8, $phone, $params, false, $uid);
            if ($result["status"] == "200") {
                $data = ["ip" => get_client_ip6(), "phone" => $phone, "time" => time()];
                \think\Db::name("sendmsglimit")->insertGetId($data);
                CacheService::set("bindtime" . $mobile, 1, 60);
                cache($prefix . $mobile, $code, 300);
                trace("new_phone_code:" . $code, "info");
                return json(["status" => 200, "msg" => "验证码发送成功"]);
            } else {
                $msg = lang("CODE_SEND_FAIL");
                $tmp = config()["public"]["ali_sms_error_code"];
                if (isset($tmp[$result["data"]["Code"]])) {
                    $msg = $tmp[$result["data"]["Code"]];
                }
                return json(["status" => 400, "msg" => $msg]);
            }
        } else {
            return json(["status" => 400, "msg" => "验证码已发送，请一分钟后再试"]);
        }
    }
    /**
     * @title 更绑手机
     * @description 接口说明
     * @param .name:tel type:int require:1  other: desc:手机号
     * @param .name:code type:int require:1  other: desc:验证码
     * @param .name:type type:int require:0  default: 1 other: desc:1为原手机验证，2为新手机验证
     * @author 上官🔪
     * @url bind_phone_change
     * @method post
     */
    public function bind_phone_change(\think\Request $request)
    {
        $data = $this->request->param();
        $id = $request->uid;
        $client = db("clients")->find($id);
        if (isset($data["type"]) && $data["type"] == 2) {
            $validate = new \think\Validate(["tel" => "require|mobile"]);
            if (!$validate->check($data)) {
                return json(["status" => 400, "msg" => $validate->getError()]);
            }
            $type = 2;
        } else {
            $data = ["tel" => $client["phonenumber"], "code" => $data["code"]];
            $type = 1;
        }
        $phone_code = $data["phone_code"];
        $tel = $data["tel"];
        $code = $data["code"];
        $data = ["phonenumber" => $tel, "code" => $code, "type" => $type];
        $rule = ["phonenumber" => "require|max:25", "code" => "integer|length:6", "type" => "integer|length:1"];
        $validate = \think\Validate::make($rule);
        if ($validate->check($data) !== true) {
            return json(["msg" => $validate->getError(), "status" => 400]);
        }
        $name = "ori_phone";
        if ($type == 2) {
            $name = "new_phone";
        }
        $rel_code = cache($name . $id . "_" . $tel);
        if (!isset($rel_code)) {
            return json(["status" => 400, "msg" => "过期的验证"]);
        }
        if ($code != $rel_code) {
            return json(["status" => 400, "msg" => "验证码错误", "code" => $code, "rel_code" => $rel_code]);
        }
        if ($type == 2) {
            $User = \app\home\model\ClientsModel::get($id);
            if ($User["phonenumber"] == $tel) {
                return json(["status" => 400, "msg" => "手机号没有变化"]);
            }
            $data = ["phonenumber" => $tel, "phone_code" => $phone_code];
            $where = ["id" => $id];
            $res = $User->save($data, $where);
            if ($res) {
                CacheService::delete("bind_change" . $id . "_status");
                CacheService::delete($name . $id . "_" . $tel);
                active_logs(
                    sprintf($this->lang["User_home_bind_phone_change_success"], substr_replace($tel, "****", 3, 4)),
                    $id,
                );
                active_logs(
                    sprintf($this->lang["User_home_bind_phone_change_success"], substr_replace($tel, "****", 3, 4)),
                    $id,
                    "",
                    2,
                );
                $email_logic = new \app\common\logic\Email();
                $email_logic->sendEmailBind($User["email"], "bind phone");
                $message_template_type = array_column(config("message_template_type"), "id", "name");
                $sms = new \app\common\logic\Sms();
                $client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
                if ($client) {
                    $params = ["username" => $User["username"], "epw_type" => "手机", "epw_account" => $tel];
                    $ret = sendmsglimit($client["phonenumber"]);
                    if ($ret["status"] == 400) {
                        return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
                    }
                    $ret = $sms->sendSms(
                        $message_template_type[strtolower("email_bond_notice")],
                        $client["phone_code"] . $client["phonenumber"],
                        $params,
                        false,
                        $id,
                    );
                    if ($ret["status"] == 200) {
                        $data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
                        \think\Db::name("sendmsglimit")->insertGetId($data);
                    }
                }
                return json(["status" => 200, "msg" => "更绑成功！"]);
            }
            return json(["status" => 400, "msg" => "绑定失败"]);
        }
        if ($type == 1) {
            session("bind_phone_change", 1);
        } else {
            session("bind_phone_change", null);
        }
        cache("bind_change" . $id . "_status", true, 600);
        return json(["status" => 200, "msg" => "ok"]);
    }
    /**
     * @title 展示 绑定微信二维码
     * @description 接口说明: 返回状态码
     * @author 上官🔪
     * @url /bind_wechat
     * @return data 微信二维码地址
     * @method get
     **/
    public function bind_wechat()
    {
        header("Content-type:text/html;charset=utf-8");
        $appid = config("appid");
        $type = $this->request->param();
        $redirect_uri = urlencode("http://f.test.idcsmart.com/bind_wechat_handle/" . $this->request->uid . "/");
        $state = md5(uniqid(rand(), true));
        session("wx_state", $state);
        $wxlogin_url =
            "https://open.weixin.qq.com/connect/qrconnect?appid=" .
            $appid .
            "&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
        return json(["status" => 200, "data" => $wxlogin_url, "msg" => ""]);
    }
    /**
     * @title 微信绑定处理
     * @description 接口说明: 微信绑定处理
     * @param .name:code type:int require:1  other: desc:用户授权
     * @param .name:state type:int require:1  other: desc:微信state
     * @param .name:id type:int require:0  other: desc:
     * @method get
     **@author 上官🔪
     * @url bind_wechat_handle/:id/
     */
    public function bind_wechat_handle(\think\Request $request)
    {
        $uid = $request->uid;
        $param = $request->only(["code", "state", "id"]);
        $wx_state = session("?wx_state");
        if (!$wx_state || $wx_state != $param["state"]) {
            return json(["status" => 400, "msg" => "无效的请求"]);
        }
        if (!isset($param["code"]) || !($param["id"] ?? null)) {
            return json(["status" => 400, "msg" => "错误的请求"]);
        }
        $appid = config("appid");
        $secret = config("secret");
        $url =
            "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" .
            $appid .
            "&" .
            "secret=" .
            $secret .
            "&" .
            "code=" .
            $param["code"] .
            "&grant_type=authorization_code";
        $res = get_data($url);
        if (isset($res["unionid"])) {
            $wechat_id = db("wechat_user")->getFieldByUnionid($res["unionid"], "id");
            if (empty($wechat_id)) {
                return $this->wechat_regist_bind($res, $appid, $param["id"]);
            } else {
                $cWhere = ["wechat_id" => $wechat_id, "id" => $uid];
                $flag = true;
                \think\Db::startTrans();
                try {
                    db("wechat_user")->where("id", $wechat_id)->delete();
                    db("clients")->where($cWhere)->setField("wechat_id", "");
                    \think\Db::commit();
                } catch (\Exception $e) {
                    $flag = false;
                    \think\Db::rollback();
                }
                if ($flag) {
                    active_logs(sprintf($this->lang["User_home_bind_wechat_handle_success"], $param["id"]), $uid);
                    active_logs(
                        sprintf($this->lang["User_home_bind_wechat_handle_success"], $param["id"]),
                        $uid,
                        "",
                        2,
                    );
                    return json(["status" => 204, "msg" => "微信解绑成功！"]);
                } else {
                    return json(["status" => 400, "msg" => "微信解绑失败！"]);
                }
            }
        }
        return json(["status" => 400, "msg" => "获取信息错误"]);
    }
    /**
     * 注册微信资料并绑定
     * @param $res :用户信息
     * @param $appid
     * @return \think\response\Json
     */
    protected function wechat_regist_bind($res, $appid, $id)
    {
        $userinfo = model("clients")->where("id", $id)->find();
        if (!empty($userinfo["wechat_id"])) {
            return json(["status" => 400, "msg" => "该账户已有绑定微信"]);
        }
        $verify_url =
            "https://api.weixin.qq.com/sns/auth?access_token=" . $res["access_token"] . "&openid=" . $res["openid"];
        $verify_res = get_data($verify_url);
        if ($verify_res["errcode"] != 0) {
            $renewal_url =
                "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=" .
                $appid .
                "&grant_type=refresh_token&refresh_token=" .
                $res["refresh_token"];
            $renewal_res = get_data($renewal_url);
            if (isset($renewal_res["errcode"])) {
                return json(["status" => 400, "msg" => "access_token续期失败"]);
            } else {
                $res = $renewal_res;
            }
        }
        $url =
            "https://api.weixin.qq.com/sns/userinfo?access_token=" . $res["access_token"] . "&openid=" . $res["openid"];
        $get_user_info = get_data($url);
        $get_user_info["update_time"] = $get_user_info["create_time"] = time();
        $success = true;
        \think\Db::startTrans();
        try {
            $result_id = model("wechat_user")->strict(false)->insertGetId($get_user_info);
            $where = ["id", $id];
            $data = ["wechat_id" => $result_id, "update_time" => time()];
            model("clients")->save($data, $where);
            \think\Db::commit();
        } catch (\Exception $e) {
            \think\Db::rollback();
            $success = false;
        }
        if (!$success) {
            return json(["status" => 400, "msg" => "微信绑定失败"]);
        }
        active_logs(sprintf($this->lang["User_home_wechat_regist_bind_success"], $appid));
        active_logs(sprintf($this->lang["User_home_wechat_regist_bind_success"], $appid), $id, "", 2);
        $email_logic = new \app\common\logic\Email();
        $email_logic->sendEmailBind($userinfo["email"], "bind wechat");
        $message_template_type = array_column(config("message_template_type"), "id", "name");
        $sms = new \app\common\logic\Sms();
        $client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
        if ($client) {
            $params = ["username" => $userinfo["username"], "epw_type" => "微信", "epw_account" => $result_id];
            $ret = sendmsglimit($client["phonenumber"]);
            if ($ret["status"] == 400) {
                return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
            }
            $ret = $sms->sendSms(
                $message_template_type[strtolower("email_bond_notice")],
                $client["phone_code"] . $client["phonenumber"],
                $params,
                false,
                $id,
            );
            if ($ret["status"] == 200) {
                $data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
                \think\Db::name("sendmsglimit")->insertGetId($data);
            }
        }
        return json(["status" => 203, "msg" => "绑定成功"]);
    }
    /**
     * @title 邮箱绑定:获取验证码
     * @description 接口说明: 返回状态码
     * @param .name:email type:str require:1  other: desc:邮箱
     **@author 上官🔪
     * @url /bind_email
     * @method post
     */
    public function bind_email(\think\Request $request)
    {
        if (configuration("shd_allow_email_send") == 0) {
            return jsonrule(["status" => 400, "msg" => "邮箱发送功能已关闭"]);
        }
        $id = $request->uid;
        $data = $this->request->param();
        if (
            !captcha_check($data["captcha"], "allow_email_bind_captcha") &&
            configuration("allow_email_bind_captcha") == 1 &&
            configuration("is_captcha") == 1
        ) {
            return json(["status" => 400, "msg" => "图形验证码有误"]);
        }
        $key = "home_client_" . $id;
        if (CacheService::has($key)) {
            return json(["status" => 200, "msg" => "发送中，请稍等"]);
        }
        CacheService::set($key, 1, 5);
        $data = $request->only("email", "post");
        $validate = new \think\Validate(["email" => "email"]);
        $validate->message(["email" => "邮箱格式错误"]);
        if (!$validate->check($data)) {
            return json(["status" => 400, "msg" => $validate->getError()]);
        }
        $clientsModel = new \app\home\model\ClientsModel();
        $res = $clientsModel->where("email", $data["email"])->find();
        if (!empty($res)) {
            if ($res["id"] != $id) {
                return json(["status" => 400, "msg" => "该邮箱已被他人绑定，请检查"]);
            }
            if ($res["email"] == $data["email"]) {
                return json(["status" => 400, "msg" => "你已绑定该邮箱，无需重复操作"]);
            }
        }
        $email = $data["email"];
        $code = mt_rand(100000, 999999);
        if (!CacheService::get("bind_time" . $email)) {
            $email_logic = new \app\common\logic\Email();
            $result = $email_logic->sendEmailCode($email, $code, "bind email");
            if ($result) {
                CacheService::set("bind_time" . $email, 1, 60);
                cache("bind_email" . $email, $code, 600);
                return json(["status" => 200, "msg" => "验证码发送成功"]);
            } else {
                $msg = lang("CODE_SEND_FAIL");
                $tmp = config()["public"]["ali_sms_error_code"];
                if (isset($tmp[$result["data"]["Code"]])) {
                    $msg = $tmp[$result["data"]["Code"]];
                }
                return json(["status" => 400, "msg" => $msg]);
            }
        } else {
            return json(["status" => 400, "msg" => "请勿频繁发送"]);
        }
    }
    /**
     * @title 邮箱绑定:执行
     * @description 接口说明:
     * @param .name:email type:str require:1  other: desc:邮箱
     * @param .name:code type:int require:1  other: desc:验证码
     **@author 上官🔪
     * @url /bind_email_handle
     * @method post
     */
    public function bind_email_handle(\think\Request $request)
    {
        $validate = new \think\Validate(["code" => "require", "email" => "email"]);
        $validate->message(["code.require" => "验证码不能为空", "email" => "email格式错误"]);
        $data = $request->only(["email", "code", "captcha"]);
        if (!$validate->check($data)) {
            return json(["error" => $validate->getError()]);
        }
        $email = $data["email"];
        $id = $request->uid;
        $rel_code = cache("bind_email" . $email);
        if ($rel_code != $data["code"]) {
            return json(["status" => 400, "msg" => "验证码错误或已过期"]);
        }
        unset($data["code"]);
        unset($data["captcha"]);
        $clientsModel = new \app\home\model\ClientsModel();
        $res = $clientsModel->cache("bind_email")->find($id);
        $msg = "绑定成功";
        if ($res["email"]) {
            $msg = "修改邮箱成功";
        }
        $data["id"] = $id;
        $log = $clientsModel->cache("bind_email")->update($data);
        if (!$log) {
            return json(["status" => 400, "msg" => "绑定出错啦"]);
        }
        $email_logic = new \app\common\logic\Email();
        $email_logic->sendEmailBind($email, "bind email");
        $User = \app\home\model\ClientsModel::get($id);
        $message_template_type = array_column(config("message_template_type"), "id", "name");
        $sms = new \app\common\logic\Sms();
        $client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
        if ($client) {
            $params = ["username" => $User["username"], "epw_type" => "邮箱", "epw_account" => $data["email"]];
            $sms->sendSms(
                $message_template_type[strtolower("email_bond_notice")],
                $client["phone_code"] . $client["phonenumber"],
                $params,
                false,
                $id,
            );
        }
        active_logs(
            sprintf($this->lang["User_home_bind_email_handle_success"], substr_replace($email, "****", 3, 4)),
            $id,
        );
        active_logs(
            sprintf($this->lang["User_home_bind_email_handle_success"], substr_replace($email, "****", 3, 4)),
            $id,
            "",
            2,
        );
        return json(["data" => $data, "status" => 200, "msg" => $msg]);
    }
    /**
     * @title 邮箱更绑:获取验证码
     * @description 接口说明:
     * @param .name:email type:str require:1  other: desc:邮箱
     * @param .name:type type:int require:0 default:1 other: desc:1：原邮箱获取，2：新邮箱获取
     * @author 上官🔪
     * @url /change_email
     * @method post
     */
    public function change_email(\think\Request $request)
    {
        if (configuration("shd_allow_email_send") == 0) {
            return jsonrule(["status" => 400, "msg" => "邮箱发送功能已关闭"]);
        }
        $type = $request->type ?? 1;
        $id = $request->uid;
        $data = $this->request->param();
        if (
            !captcha_check($data["captcha"], "allow_email_bind_captcha") &&
            configuration("allow_email_bind_captcha") == 1 &&
            configuration("is_captcha") == 1
        ) {
            return json(["status" => 400, "msg" => "图形验证码有误"]);
        }
        $key = "home_client_" . $id;
        if (CacheService::has($key)) {
            return json(["status" => 200, "msg" => "发送中，请稍等"]);
        }
        CacheService::set($key, 1, 5);
        $data = $request->only("email", "post");
        $validate = new \think\Validate(["email" => "require|email"]);
        $validate->message(["email" => "邮箱格式错误"]);
        if (!$validate->check($data)) {
            return json(["status" => 400, "msg" => $validate->getError()]);
        }
        $clientsModel = new \app\home\model\ClientsModel();
        $data["id"] = $id;
        $name = "ori_email_";
        $email = $data["email"];
        if ($type == 1) {
            $res = $clientsModel->where($data)->find();
            if (empty($res)) {
                return json(["status" => 400, "msg" => "你没有绑定该邮箱"]);
            }
            $code = mt_rand(100000, 999999);
            if (!CacheService::get("bindtime" . $email)) {
                $email_logic = new \app\common\logic\Email();
                $result = $email_logic->sendEmailCode($email, $code, "bind email");
                if ($result) {
                    CacheService::set("bindtime" . $email, 1, 60);
                    cache($name . $email, $code, 600);
                    return json(["status" => 200, "msg" => "验证码发送成功"]);
                } else {
                    $msg = lang("CODE_SEND_FAIL");
                    $tmp = config()["public"]["ali_sms_error_code"];
                    if (isset($tmp[$result["data"]["Code"]])) {
                        $msg = $tmp[$result["data"]["Code"]];
                    }
                    return json(["status" => 400, "msg" => $msg]);
                }
            } else {
                return json(["status" => 400, "msg" => "请勿频繁发送"]);
            }
        } else {
            $status = cache("email_change_" . $id . "_status", true, 600);
            if (!$status) {
                return json(["status" => 400, "msg" => " 过期的请求，请重新验证"]);
            }
            $name = "new_email_";
            $res = $clientsModel->where("email", $data["email"])->find();
            if (!empty($res)) {
                return json(["status" => 400, "msg" => "该邮箱已被他人绑定，请检查"]);
            }
            $code = mt_rand(100000, 999999);
            if (!CacheService::get("bindtime2" . $email)) {
                $email_logic = new \app\common\logic\Email();
                $result = $email_logic->sendEmailCode($email, $code, "bind email");
                if ($result) {
                    CacheService::set("bindtime2" . $email, time(), 60);
                    cache($name . $email, $code, 600);
                    return json(["status" => 200, "msg" => "验证码发送成功"]);
                } else {
                    $msg = lang("CODE_SEND_FAIL");
                    $tmp = config()["public"]["ali_sms_error_code"];
                    if (isset($tmp[$result["data"]["Code"]])) {
                        $msg = $tmp[$result["data"]["Code"]];
                    }
                    return json(["status" => 400, "msg" => $msg]);
                }
            } else {
                return json(["status" => 400, "msg" => "请勿频繁发送"]);
            }
        }
    }
    /**
     * @title 邮箱更绑:执行验证
     * @description 接口说明:
     * @param .name:email type:str require:1  other: desc:邮箱
     * @param .name:code type:str require:1  other: desc:验证码
     * @param .name:type type:int require:0 default:1 other: desc:1：原邮箱验证，2：新邮箱验证
     * @author 上官🔪
     * @url /change_email_handle
     * @method post
     */
    public function change_email_handle(\think\Request $request)
    {
        $email = $request->email;
        $id = $request->uid;
        $code = $request->code;
        $type = $request->type ?? 1;
        $validate = new \think\Validate(["email" => "require|email"]);
        $validate->message(["email" => "邮箱格式错误"]);
        if (!$validate->check(["email" => $email])) {
            return json(["status" => 400, "msg" => $validate->getError()]);
        }
        $data = $this->request->param();
        $name = "ori_email_";
        if ($type == 2) {
            $name = "new_email_";
            $status = cache("email_change_" . $id . "_status");
            if (!$status) {
                return json(["status" => 400, "msg" => "过期的验证"]);
            }
        }
        $rel_code = cache($name . $email);
        if (!isset($rel_code)) {
            return json(["status" => 400, "msg" => "过期的验证"]);
        }
        if ($code != $rel_code) {
            return json(["status" => 400, "msg" => "验证码错误"]);
        }
        if ($type == 2) {
            $data = ["email" => $email];
            $User = \app\home\model\ClientsModel::get($id);
            $where = ["id" => $id];
            $res = $User->save($data, $where);
            if ($res) {
                cache("email_change_" . $id . "_status", true, 600);
                active_logs(sprintf($this->lang["User_home_change_email_handle_success"], $email), $id);
                active_logs(sprintf($this->lang["User_home_change_email_handle_success"], $email), $id, "", 2);
                $email_logic = new \app\common\logic\Email();
                $email_logic->sendEmailBind($email, "bind email");
                $message_template_type = array_column(config("message_template_type"), "id", "name");
                $sms = new \app\common\logic\Sms();
                $client = check_type_is_use($message_template_type[strtolower("email_bond_notice")], $id, $sms);
                if ($client) {
                    $params = ["username" => $User["username"], "epw_type" => "邮箱", "epw_account" => $data["email"]];
                    $ret = sendmsglimit($client["phonenumber"]);
                    if ($ret["status"] == 400) {
                        return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
                    }
                    $ret = $sms->sendSms(
                        $message_template_type[strtolower("email_bond_notice")],
                        $client["phone_code"] . $client["phonenumber"],
                        $params,
                        false,
                        $id,
                    );
                    if ($ret["status"] == 200) {
                        $data = ["ip" => get_client_ip6(), "phone" => $client["phonenumber"], "time" => time()];
                        \think\Db::name("sendmsglimit")->insertGetId($data);
                    }
                }
                return json(["status" => 200, "msg" => "更绑成功！"]);
            }
            return json(["status" => 400, "msg" => "绑定失败"]);
        }
        if ($type == 1) {
            session("bind_email_change", 1);
        } else {
            session("bind_email_change", null);
        }
        cache("email_change_" . $id . "_status", 600);
        return json(["status" => 200, "msg" => "ok"]);
    }
    /**
     * @title 用户日志
     * @description 接口说明
     * @param .name:page type:int require:0 default:1 other: desc:分页
     * @param .name:page_size type:int require:0 default:10 other: desc:页数据
     * @param .name:action type:int require:0 default:空字符传 other: desc:login=登录日志
     * @param name:keywords type:int  require:0  default: other: desc:关键字
     * @return .id:
     * @return .username:用户名
     * @return .url:拜访资源
     * @return .ip:ip
     * @return .create_time:时间戳
     * @throws
     * @author 上官🔪
     * @url /user_action_log/:page/
     * @method GET
     */
    public function user_action_log(\think\Request $request)
    {
        $page = $request->param("page", 1);
        $limit = $request->param("limit", 10);
        $orderby = $request->param("orderby", "id");
        $sort = $request->param("sort", "desc");
        $where[] = ["uid", "=", $request->uid];
        $param = $request->param();
        $where[] = ["type", "=", 1];
        $res = \think\Db::name("activity_log")
            ->where($where)
            ->where(function (\think\db\Query $query) use ($param) {
                if (!empty($param["keywords"])) {
                    $search_desc = $param["keywords"];
                    $query->whereOr("description", "like", "%{$search_desc}%");
                    $query->whereOr("ipaddr", "like", "%{$search_desc}%");
                }
            })
            ->withAttr("ipaddr", function ($value, $data) {
                if (empty($data["port"])) {
                    return $value;
                } else {
                    return $value .= ":" . $data["port"];
                }
            })
            ->page($page, $limit)
            ->order($orderby, $sort)
            ->select()
            ->toArray();
        foreach ($res as $key => $value) {
            $res[$key]["username"] = $value["user"];
            $res[$key]["url"] = $value["description"];
            $res[$key]["ip"] = $value["ipaddr"];
        }
        $count = \think\Db::name("activity_log")
            ->where($where)
            ->where(function (\think\db\Query $query) use ($param) {
                if (!empty($param["keywords"])) {
                    $search_desc = $param["keywords"];
                    $query->whereOr("description", "like", "%{$search_desc}%");
                    $query->whereOr("ipaddr", "like", "%{$search_desc}%");
                }
            })
            ->count();
        $data = ["sum" => $count, "list" => $res, "page" => $page, "limit" => $limit];
        return json(["data" => $data, "status" => "200", "msg" => "ok"]);
    }
    /**
     * @title 地区列表
     * @description 接口说明
     * @param .name:pid type:int require:0 default:1 other: 父级区域id
     * @return .area_id: 地区id
     * @return .name:名称
     * @return .pid:父级id
     * @throws
     * @author 上官🔪
     * @url /areas/:pid/
     * @method GET
     */
    public function areas()
    {
        $areas = model("Areas")->listQuery();
        return json(["msg" => "ok", "status" => 200, "data" => $areas]);
    }
    /**
     * @title 获取国家列表
     * @description 接口说明
     * @return .id: 国家id
     * @return .name:名称
     * @author 上官🔪
     * @url /country
     * @method GET
     */
    public function country()
    {
        $arr = config("country.country");
        return json(["data" => $arr, "status" => 200, "msg" => "ok"]);
    }
    /**
     * @title 用户修改密码
     * @description 接口说明
     * @param .name:old_password type:string require:0 default:1 other: 旧密码
     * @param .name:password type:string require:1 default:1 other: desc:新密码
     * @param .name:re_password type:string require:1 default:1 other: desc:重复新密码
     * @param .name:code type:string require:1 default:1 other: desc:验证码
     * @param .name:flag type:string require:1 default:1 other: desc:1为有原密码2为没有原密码
     * @author wyh
     * @url modify_password
     * @method POST
     */
    public function modifyPassword()
    {
        if ($this->request->isPost()) {
            $clientId = $this->request->uid;
            $data = $this->request->param();
            $flag = $data["flag"];
            if ($flag == 1) {
                $validate = new \think\Validate([
                    "old_password" => "require|min:6|max:32",
                    "password" => "require|min:6|max:32",
                    "re_password" => "require|min:6|max:32",
                ]);
                if (
                    !captcha_check($data["captcha"], "allow_resetpwd_captcha") &&
                    configuration("allow_resetpwd_captcha") == 1 &&
                    configuration("is_captcha") == 1
                ) {
                    return json(["status" => 400, "msg" => "图形验证码有误"]);
                }
            } else {
                $validate = new \think\Validate([
                    "password" => "require|min:6|max:32",
                    "re_password" => "require|min:6|max:32",
                ]);
                if (
                    !captcha_check($data["captcha"], "allow_setpwd_captcha") &&
                    configuration("allow_setpwd_captcha") == 1 &&
                    configuration("is_captcha") == 1
                ) {
                    return json(["status" => 400, "msg" => "图形验证码有误"]);
                }
            }
            if (!$validate->check($data)) {
                return json(["status" => 400, "msg" => $validate->getError()]);
            }
            $client = \think\Db::name("clients")->where("id", $clientId)->find();
            $oldPassword = $data["old_password"];
            $password = $data["password"];
            $rePassword = $data["re_password"];
            if ($password != $rePassword) {
                return json(["status" => 400, "msg" => \lang("两次输入密码不一致")]);
            }
            if ($flag == 1) {
                if (cmf_compare_password($oldPassword, $client["password"])) {
                    if ($password == $rePassword) {
                        if (cmf_compare_password($password, $client["password"])) {
                            return json(["status" => 400, "msg" => lang("LOGIN_NEW_SAME")]);
                        } else {
                            $res = $this->secondVerify();
                            if ($res["status"] != 200) {
                                return jsons($res);
                            }
                            CacheService::set(
                                "client_user_update_pass_" . $clientId,
                                $this->request->time(),
                                7200,
                            );
                            \think\Db::name("clients")
                                ->where("id", $clientId)
                                ->update(["password" => cmf_password($password)]);
                            active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId);
                            active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId, "", 2);
                            hook("client_reset_password", [
                                "uid" => $clientId,
                                "password" => html_entity_decode($password, ENT_QUOTES),
                            ]);
                            return json(["status" => 200, "msg" => \lang("LOGIN_UPDATE")]);
                        }
                    } else {
                        return json(["status" => 400, "msg" => \lang("LOGIN_NO_SAME")]);
                    }
                } else {
                    return json(["status" => 400, "msg" => \lang("LOGIN_NO")]);
                }
            } else {
                if ($password == $rePassword) {
                    if (cmf_compare_password($password, $client["password"])) {
                        return json(["status" => 400, "msg" => lang("LOGIN_NEW_SAME")]);
                    } else {
                        $res = $this->secondVerify();
                        if ($res["status"] != 200) {
                            return jsons($res);
                        }
                        CacheService::set("client_user_update_pass_" . $clientId, $this->request->time(), 7200);
                        \think\Db::name("clients")
                            ->where("id", $clientId)
                            ->update(["password" => cmf_password($password)]);
                        active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId);
                        active_logs(sprintf($this->lang["User_home_modifyPassword_success"]), $clientId, "", 2);
                        return json(["status" => 200, "msg" => \lang("LOGIN_UPDATE")]);
                    }
                } else {
                    return json(["status" => 400, "msg" => \lang("LOGIN_NO_SAME")]);
                }
            }
        }
        return json(["status" => 400, "msg" => \lang("ERROR MESSAGE")]);
    }
    /**
     * 时间 2020/4/30 16:40
     * @title 登录短信提醒
     * @desc 登录短信提醒
     * @url login_sms_reminder
     * @method  POST
     * @param .name:status type:int require:1 default:0 other: desc:开启1=开启0=关闭
     * @param .name:code type:int require:0 default:0 other: desc:关闭的时候需要短信验证
     * @author liyongjun
     * @version v1
     */
    public function loginSmsReminder()
    {
        $status = \intval($this->request->post("status", 0));
        $code = \intval($this->request->post("code", 0));
        if ($status !== 0 && $status !== 1) {
            return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
        }
        $user = \think\Db::name("clients")->find($this->request->uid);
        if (!isset($user["phonenumber"][0]) && !isset($user["email"][0])) {
            return json(["status" => 400, "msg" => "请先绑定手机号"]);
        }
        $data = $this->request->param();
        if ($status === 0) {
            if ($code <= 0) {
                return json(["status" => 400, "msg" => "验证码不正确"]);
            }
            $tmp = \intval(cache("remind_" . $user["phone_code"] . "-" . $user["phonenumber"]));
            if ($code !== $tmp) {
                return json(["status" => 400, "msg" => "验证码不正确"]);
            }
        }
        $res = \think\Db::name("clients")
            ->where("id", $this->request->uid)
            ->update(["is_login_sms_reminder" => $status]);
        if ($res) {
            if ($status == 1) {
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid);
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid, "", 2);
            } else {
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid);
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid, "", 2);
            }
            return json(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
        }
        return json(["status" => 400, "msg" => lang("UPDATE FAIL")]);
    }
    /**
     * @title 登录提醒关闭验证短信发送
     * @description 接口说明:登录提醒关闭验证短信发送(phone)
     * @author lyj
     * @param .name:captcha type:int require:0 default:0 other: desc:
     * @url /remind_send
     * @method get
     */
    public function remindSend()
    {
        $data = $this->request->param();
        if (
            !captcha_check($data["captcha"], "allow_cancel_sms_captcha") &&
            configuration("allow_cancel_sms_captcha") == 1 &&
            configuration("is_captcha") == 1
        ) {
            return json(["status" => 400, "msg" => "图形验证码有误"]);
        }
        $client = \think\Db::name("clients")->find($this->request->uid);
        $mobile = $client["phone_code"] . "-" . $client["phonenumber"];
        if ($client["phone_code"] == "+86" || $client["phone_code"] == "86") {
            $phone = $client["phonenumber"];
        } else {
            if (substr($client["phone_code"], 0, 1) == "+") {
                $phone = substr($client["phone_code"], 1) . "-" . $client["phonenumber"];
            } else {
                $phone = $client["phone_code"] . "-" . $client["phonenumber"];
            }
        }
        if (CacheService::has("remind_" . $mobile . "_time")) {
            return json(["status" => 400, "msg" => lang("CODE_SENDED")]);
        }
        $code = mt_rand(100000, 999999);
        $params = ["code" => $code];
        $sms = new \app\common\logic\Sms();
        $ret = sendmsglimit($phone);
        if ($ret["status"] == 400) {
            return json(["status" => 400, "msg" => lang("SEND FAIL") . ":" . $ret["msg"]]);
        }
        $result = $sms->sendSms(8, $phone, $params, false, $client["id"]);
        if ($result["status"] == 200) {
            $data = ["ip" => get_client_ip6(), "phone" => $phone, "time" => time()];
            \think\Db::name("sendmsglimit")->insertGetId($data);
            cache("remind_" . $mobile, $code, 300);
            CacheService::set("remind_" . $mobile . "_time", $code, 60);
            return json(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
        } else {
            $msg = lang("CODE_SEND_FAIL");
            $tmp = config()["public"]["ali_sms_error_code"];
            if (isset($tmp[$result["data"]["Code"]])) {
                $msg = $tmp[$result["data"]["Code"]];
            }
            return json(["status" => 400, "msg" => $msg]);
        }
    }
    /**
     * @title 登录提醒关闭验证邮件发送
     * @description 接口说明:登录提醒关闭验证邮件发送(email)
     * @author wyh
     * @param .name:captcha type:int require:0 default:0 other: desc:
     * @url /remind_email_send
     * @method get
     */
    public function remindEmailSend()
    {
        $data = $this->request->param();
        if (
            !captcha_check($data["captcha"], "allow_cancel_email_captcha") &&
            configuration("allow_cancel_email_captcha") == 1 &&
            configuration("is_captcha") == 1
        ) {
            return json(["status" => 400, "msg" => "图形验证码有误"]);
        }
        $data = \think\Db::name("clients")->find($this->request->uid);
        $validate = new \think\Validate(["email" => "require"]);
        $validate->message(["email.require" => "邮箱不能为空"]);
        if (!$validate->check($data)) {
            return json(["status" => 400, "msg" => $validate->getError()]);
        }
        $email = trim($data["email"]);
        if (\think\facade\Validate::isEmail($email)) {
            $code = mt_rand(100000, 999999);
            if (time() - session("email_remind" . $email) >= 60) {
                $email_logic = new \app\common\logic\Email();
                $result = $email_logic->sendEmailCode($email, $code);
                session("email_remind" . $email, time());
                if ($result) {
                    cache("email_remind" . $email, $code, 600);
                    return json(["status" => 200, "msg" => lang("CODE_SEND_SUCCESS")]);
                } else {
                    return json(["status" => 400, "msg" => lang("CODE_SEND_FAIL")]);
                }
            } else {
                return json(["status" => 400, "msg" => lang("CODE_SENDED")]);
            }
        } else {
            return json(["status" => 400, "msg" => lang("EMAIL_ERROR")]);
        }
    }
    /**
     * 时间 2020/12/11 16:40
     * @title 登录邮件提醒
     * @desc 登录邮件提醒
     * @url login_email_reminder
     * @method  POST
     * @param .name:status type:int require:1 default:0 other: desc:开启1=开启0=关闭
     * @param .name:code type:int require:0 default:0 other: desc:关闭的时候需要短信验证
     * @author wyh
     * @version v1
     */
    public function loginEmailReminder()
    {
        $status = \intval($this->request->post("status", 0));
        $code = \intval($this->request->post("code", 0));
        if ($status !== 0 && $status !== 1) {
            return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
        }
        $user = \think\Db::name("clients")->find($this->request->uid);
        if (!isset($user["phonenumber"][0]) && !isset($user["email"][0])) {
            return json(["status" => 400, "msg" => "请先绑定邮箱"]);
        }
        $data = $this->request->param();
        if ($status === 0) {
            if ($code <= 0) {
                return json(["status" => 400, "msg" => "验证码不正确"]);
            }
            $tmp = \intval(cache("email_remind" . $user["email"]));
            if ($code !== $tmp) {
                return json(["status" => 400, "msg" => "验证码不正确"]);
            }
        }
        $res = \think\Db::name("clients")
            ->where("id", $this->request->uid)
            ->update(["email_remind" => $status]);
        if ($res) {
            if ($status == 1) {
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid);
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success1"]), $this->request->uid, "", 2);
            } else {
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid);
                active_logs(sprintf($this->lang["User_home_loginSmsReminder_success2"]), $this->request->uid, "", 2);
            }
            return json(["status" => 200, "msg" => lang("UPDATE SUCCESS")]);
        }
        return json(["status" => 400, "msg" => lang("UPDATE FAIL")]);
    }
    /**
     * 时间 2020/5/9 17:25
     * @title 获取地址信息
     * @desc 获取地址信息
     * @url get_areas
     * @method  GET
     * @return areas  - 地址信息(数组)
     * @return country  - 国家信息(数组)
     * @author liyongjun
     * @version v1
     */
    public function getAreas()
    {
        $country = config("country.country");
        $areas = \think\Db::name("areas")
            ->field("area_id,pid,name")
            ->where("show", 1)
            ->where("data_flag", 1)
            ->select()
            ->toArray();
        $areas = getStructuredTree($areas);
        return jsons([
            "status" => 200,
            "msg" => \lang("SUCCESS MESSAGE"),
            "data" => ["areas" => $areas, "country" => $country],
        ]);
    }
    /**
     * 时间 2020/6/24 11:25
     * @title 获取销售员
     * @desc 获取销售员
     * @url get_saler
     * @method  GET
     * @return list:销售员数据@
     * @list  id:销售员id
     * @list  user_nickname:销售员昵称
     * @list  user_email:销售员邮箱
     * @return saleset:是否显示销售@
     * @author lgd
     * @version v1
     */
    public function getSaler()
    {
        $uid = $this->request->uid;
        $client = db("clients")->field("sale_id")->where("id", $uid)->find();
        if (empty($client["sale_id"]) || $client["sale_id"] == 0) {
            $data = db("user")
                ->field("id,user_nickname")
                ->where("is_sale", 1)
                ->where("sale_is_use", 1)
                ->select()
                ->toArray();
        } else {
            $data = db("user")->field("id,user_nickname")->where("id", $client["sale_id"])->select()->toArray();
        }
        return jsons(["status" => 200, "msg" => "", "data" => $data, "saleset" => configuration("sale_setting")]);
    }
    /**
     * 时间 2020/6/24 11:25
     * @title 设定销售员
     * @desc 设定销售员
     * @param name:uid type:int  require:0  default:1 other: desc:用户id
     * @param name:sale_id type:int  require:0  default:1 other: desc:销售员id
     * @param name:type type:int  require:0  default:1 other: desc:1下单2注册
     * @url set_saler
     * @method  POST
     * @author lgd
     * @version v1
     */
    public function setSaler()
    {
        $param = $this->request->param();
        $sale_id = isset($param["sale_id"]) ? intval($param["sale_id"]) : 0;
        $uid = intval($param["uid"]) ?? 0;
        if (!\think\Db::name("clients")->where("id", $uid)->value("sale_id")) {
            return jsons(["status" => 200, "msg" => "设定成功"]);
        }
        if (!$sale_id) {
            $sale_setting = configuration("sale_setting");
            if ($sale_setting == 0) {
                return jsons(["status" => 200, "msg" => "设定成功"]);
            } elseif ($sale_setting == 1) {
                $sale_auto_setting = configuration("sale_auto_setting");
                if ($sale_auto_setting == 1) {
                    $data = db("user")
                        ->field("id,user_nickname,user_email")
                        ->where("is_sale", 1)
                        ->where("sale_is_use", 1)
                        ->select()
                        ->toArray();
                    $num = rand(0, count($data));
                    if (count($data) == 1) {
                        $num = 0;
                    }
                    $sale_id = $data[$num]["id"];
                } else {
                    $setsalerinc = configuration("setsalerinc") ?? 0;
                    $data = db("user")
                        ->field("id")
                        ->where("is_sale", 1)
                        ->where("id", ">", $setsalerinc)
                        ->order("id", "asc")
                        ->where("sale_is_use", 1)
                        ->find();
                    if (empty($data)) {
                        $data = db("user")
                            ->field("id")
                            ->where("is_sale", 1)
                            ->where("sale_is_use", 1)
                            ->order("id", "asc")
                            ->find();
                    }
                    $sale_id = $data["id"];
                    updateConfiguration("setsalerinc", $sale_id);
                }
            }
        }
        $data = db("user")
            ->field("id,user_nickname,user_email")
            ->where("id", $sale_id)
            ->where("is_sale", 1)
            ->where("sale_is_use", 1)
            ->find();
        if ($sale_id != 0 && empty($data)) {
            return jsons(["status" => 400, "msg" => "失败"]);
        }
        if ($sale_id) {
            $res = Db("clients")
                ->where("id", $uid)
                ->update(["sale_id" => $sale_id]);
        }
        return jsons(["status" => 200, "msg" => "设定成功"]);
    }
    /**
     * @title 注销
     * @description 接口说明:
     **@author 上官🔪
     * @url /logOut
     * @method get
     */
    public function logOut()
    {
        $authorization = explode(" ", $this->request->header()["authorization"])[1];
        CacheService::delete("client_user_login_token_" . $authorization);
        active_logs(sprintf($this->lang["User_home_loginout"], $this->request->uid), $this->request->uid, 1);
        active_logs(sprintf($this->lang["User_home_loginout"], $this->request->uid), $this->request->uid, 1, 2);
        if (!empty($this->request->uid)) {
            hook("client_logout", ["uid" => $this->request->uid]);
        }
        return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
    }
}
