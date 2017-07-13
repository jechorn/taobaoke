<?php
/** .-------------------------------------------------------------------
 * |    Software: []
 * | Description:
 * |        Site: www.jechorn.cn
 * |-------------------------------------------------------------------
 * |      Author: 王志传
 * |      Email : <jechorn@163.com>
 * |  CreateTime: 2017/6/13-23:19
 * | Copyright (c) 2016-2019, www.jechorn.cn. All Rights Reserved.
 * '-------------------------------------------------------------------*/

namespace app\admin\controller;

use api\taobao\top\request\TbkUatmFavoritesItemGetRequest;
use api\taobao\top\TopClient;
use app\admin\common\Api;
use think\Config;
use think\Db;
use think\exception\PDOException;
use think\Validate;

class Collect extends Api
{
    private $collectModel;
    private $collectMsg = [];
    private $collectionField = 'num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,click_url,nick,seller_id,volume,tk_rate,zk_final_price_wap,event_start_time,event_end_time,category,coupon_click_url,coupon_end_time,coupon_info,coupon_start_time,commission_rate,coupon_total_count,coupon_remain_count';
    private $total = 0;


    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->collectModel = new  \app\admin\model\Collect();
    }

    public function index()
    {
        $cur = $this->request->param('page', 1, 'intval');
        $total = $this->collectModel->collectCount();
        $page = ceil($total / config('paginate.list_rows'));
        if ($cur > $page) {
            $cur = $page;
        } elseif ($cur < 1) {
            $cur = 1;
        }
        $info = [];
        if ($total > 0) {
            $info = $this->collectModel->getCollect('', $cur);
        }
        $this->assign('collectList', $info);
        $this->assign('pageInfo', ['page' => $page, 'cur' => $cur, 'total' => $total]);
        return $this->fetch();
    }

    public function add()
    {
        $cate = Db::name('cate')->field('id,cate_name')->order('sort DESC')->select();
        $activity = Db::name('activity')->field('id,activity_name')->order('sort DESC')->select();
        $this->assign('cateInfo', $cate);
        $this->assign('activityInfo', $activity);
        return $this->fetch();
    }


    public function addHandle()
    {
        $this->jump();
        $data = $this->request->param('', '', 'htmlspecialchars');
        $data['type'] = isset($data['type']) ? isset($data['type']) : 0;
        $rules = [
            'collect_name|采集器' => 'require|length:2,10',
            'cate_id|分类ID' => 'require|integer',
            'favorites_id|选库表ID' => 'require|unique:collect|integer|gt:0',
            'favorites_name|选库表名称' => 'max:15',
            'adzone_id|adzone_id' => 'require|integer|gt:0',
            'activity_id|活动字段不合法' => 'integer',
        ];
        $msg = [
            'adzone_id.integer' => 'adzone_id字段填写不合法，请检查长度',
            'cate_id.integer' => '分类ID填写不合法，请检查长度',
            'favorites_id.integer' => '选库表ID填写不合法，请检查长度',
        ];
        $validate = new Validate($rules, $msg);
        if (!$validate->check($data)) {
            return json([
                'status' => 'error',
                'errorMsg' => $validate->getError()
            ]);
        }
        $addData = [
            'name' => $data['collect_name'],
            'cate_id' => $data['cate_id'],
            'favorites_id' => $data['favorites_id'],
            'favorites_name' => $data['favorites_name'],
            'adzone_id' => $data['adzone_id'],
            'type' => $data['type'],
            'activity_id' => $data['activity_id'],
            'create_time' => date('Y-m-d H:i:s', time()),

        ];
        if (!isset($data['collect_id'])) {
            $info = $this->collectModel->insertCollect($addData);
            $title = '采集器规则添加';

        } else {
            $addData['id'] = $data['collect_id'];
            $title = '采集器规则修改';
            $info = $this->collectModel->updateCollect($addData);

        }
        if ($info) {
            return json([
                'status' => 'ok',
                'errorMsg' => ''
            ]);
        } else {
            return json([
                'status' => 'ok',
                'errorMsg' => $title . '失败'
            ]);
        }

    }

    public function delete()
    {
        $this->jump();
        $id = $this->request->param('id', '', 'intval');
        $info = Db::name('collect')->where(['id' => $id])->delete();
        if ($info) {
            return json([
                'status' => 'ok',
                'errorMsg' => ''
            ]);
        } else {
            return json([
                'status' => 'error',
                'errorMsg' => '数据没被删除'
            ]);
        }
    }

    public function deleteAll()
    {
        $this->jump();
        $data = $this->request->param('', '', 'intval');
        if (!empty($data['ids'])) {
            $info = Db::name('collect')->delete($data['ids']);
            if ($info) {
                return json([
                    'status' => 'ok',
                    'errorMsg' => ''
                ]);
            } else {
                return json([
                    'status' => 'error',
                    'errorMsg' => '数据没被删除'
                ]);
            }
        }
        return json([
            'status' => 'error',
            'errorMsg' => '非法操作'
        ]);
    }

    //阿里妈妈所有选库表列表
    public function getAliSelection()
    {
//        $fileData = include(ROOT_PATH . 'public/data/data.php');
//        if (isset($fileData['create_time'])) {
//            $diffTime = time() - intval($fileData['create_time']);
//            if ($diffTime <= 24 * 3600) {
//                $this->assign('list', $fileData['tbk_favorites']);
//                return $this->fetch('list');
//            }
//        }

        if ($data = $this->setAliSelection(2)) {
            //$fileData = include(ROOT_PATH . 'public/data/data.php');
            $this->assign('list', $data);

        } else {
            $this->assign('errorMsg', $this->getErrorMsg());
        }
        return $this->fetch('list');

    }

    /**
     * @param $collectData 采集规则的信息数组，必须包含adzone_id，favorites_id，favorites_name，cate_id键值对
     * @return bool
     * status 状态码 1 代表信息入库前的必要检查不通过
     *              2  相关信息检验通过，并且所有数据采集入库成功
     *              3  系统已经正常响应，但是API服务器返回错误码
     *              4  查询到的数据为空
     *              5 采集数据成功，但是入库失败，
     *              6 相关信息检验通过，循环中一个循环数据采集入库成功
     */
    private function selectCollect(array $collectData)
    {
        if (!$this->issetApi()) {
            $this->error($this->errorMsg, url('system/home', ['type' => 2, 'url' => 2]));
        }
        $adzoneId = Config::get('system.adzone_id');
        include EXTEND_PATH . DS . 'api/taobao/TopSdk.php';
        $c = new TopClient();
        $c->appkey = Config::get('system.app_key');
        $c->secretKey = Config::get('system.app_secret');
        $c->format = 'json';
        $req = new TbkUatmFavoritesItemGetRequest();
        //$pageNum = 1;
        $pageSize = 100;
        $req->setPlatform("1");
        $req->setPageSize("{$pageSize}");
        $req->setAdzoneId("{$adzoneId}");
        $req->setFields($this->collectionField);
        $req->setFavoritesId("{$collectData['favorites_id']}");

        $total = 0;
        for ($i = 2; $i >= 1; $i--) {
            $req->setPageNo("{$i}");
            $resp = $c->execute($req);
            $resp = json_decode(json_encode($resp, JSON_UNESCAPED_UNICODE), true);

            if (isset($resp['error_response']) || isset($resp['code'])) {

                $this->collectMsg = [
                    'status' => 3,
                    'msg' => '淘宝服务器响应失败'
                ];
                //continue;
                //return $this->collectMsg;

            } elseif (isset($resp['total_results']) && intval($resp['total_results']) === 0) {
                $this->collectMsg = [
                    'status' => 4,
                    'success_num' => 0,
                    'favorites_title' => $collectData['favorites_name'],
                    'msg' => "{$collectData['favorites_name']}没有数据",
                ];

            } else {
                if($collectData['is_qiang'] == 1){
                    $data = $this->handleData($resp['results']['uatm_tbk_item'], $collectData['activity_id'],$collectData['is_qiang'],$collectData['start_time'],$collectData['end_time']);

                }else{
                    $data = $this->handleData($resp['results']['uatm_tbk_item'], $collectData['activity_id']);
                }

                $sql = Db::name('products')->fetchSql(true)->insertAll($data, false);
                $sql .= $this->updateSql;
                $sql .= ',`activity_id`=VALUES(`activity_id`),`is_qiang`=VALUES(`is_qiang`),`qiang_start_time`=VALUES(`qiang_start_time`),`qiang_end_time`=VALUES(`qiang_end_time`)';
                $pdo = $this->pdoConnect();
                $info = $pdo->exec($sql);
                $this->collectMsg = [
                    'status' => 2,
                    'success_num' => $info,
                    'favorites_title' => $collectData['favorites_name'],
                    'msg' => "采集{$collectData['favorites_name']}选库成功，共入库更新{$info}数据",
                ];
                $total = $total + $info;

            }


        }
        $this->collectMsg = [
            'status' => 2,
            'success_num' => $total,
            'favorites_title' => $collectData['favorites_name'],
            'msg' => "采集{$collectData['favorites_name']}选库成功，共入库更新{$total}数据",
        ];

        return true;

    }

    /**
     * @return bool
     * 自动一键采集阿里妈妈选库表的商品
     * status 状态码 1 代表信息入库前的必要检查不通过
     *              2  相关信息检验通过，并且所有数据采集入库成功
     *              3  系统已经正常响应，但是API服务器返回错误码
     *              4  查询到的数据为空
     *              5 采集数据成功，但是入库失败，
     *              6 相关信息检验通过，循环中一个循环数据采集入库成功
     */

    private function autoCollect()
    {
        $favoritesData = $this->setAliSelection();
        if ($favoritesData) {
            //$fileData = include(ROOT_PATH . 'public/data/data.php');
            $adzoneId = Config::get('system.adzone_id');
            $c = new TopClient();
            $c->appkey = Config::get('system.app_key');
            $c->secretKey = Config::get('system.app_secret');
            $c->format = 'json';
            $req = new TbkUatmFavoritesItemGetRequest();
            //$pageNum = 1;
            $pageSize = 100;
            $req->setPlatform("1");
            $req->setPageSize("{$pageSize}");
            $req->setAdzoneId("{$adzoneId}");
            $req->setFields($this->collectionField);
            $cate = Db::name('cate')->field('id,cate_name')->select();
            if (empty($cate)) {
                $this->collectMsg = [
                    'status' => 1,
                    'msg' => '请先添加分类'
                ];
                //return $this->collectMsg;
                return false;
            }

            $count = 0;
            foreach ($favoritesData as $vo) {
                $count++;
                for ($i = 1; $i <= 2; $i++) {
                    $req->setFavoritesId("{$vo['favorites_id']}");
                    $req->setPageNo("{$i}");
                    $resp = $c->execute($req);
                    $resp = json_decode(json_encode($resp, JSON_UNESCAPED_UNICODE), true);

                    if (isset($resp['error_response']) || isset($resp['code'])) {
                        $this->collectMsg = [
                            'status' => 3,
                            'msg' => '淘宝服务器响应失败'
                        ];
                        //return $this->collectMsg;

                    }elseif (isset($resp['total_results']) && intval($resp['total_results']) === 0){
                        $this->collectMsg = [
                            'status' => 4,
                            'count' => $count,
                            'success_num' => 0,
                            'favorites_title' => "{$vo['favorites_title']}",
                            'msg' => "{$vo['favorites_title']}没有数据",
                        ];

                    } else {
                        $data = $this->handleData($resp['results']['uatm_tbk_item']);
                        //$len = ceil($resp['total_results']/$pageSize);
                        $sql = Db::name('products')->fetchSql(true)->insertAll($data, false);
                        //$sql = substr_replace($sql, 'INSERT IGNORE', 0, 6);
                        //$sql .= ' ON DUPLICATE KEY UPDATE `num_iid`=VALUES(`num_iid`),`activity_id`=0)';
                        $sql .= $this->updateSql;
                        $pdo = $this->pdoConnect();
                        $info = $pdo->exec($sql);
                        if ($info) {
                            $this->collectMsg = [
                                'status' => 6,
                                'count' => $count,
                                'success_num' => $info,
                                'favorites_title' => "{$vo['favorites_title']}",
                                'msg' => "正在采集第{$count}个选库，共采集{$info}数据",
                            ];
                            $this->total = $this->total + $info;

                        } else {
                            $this->collectMsg = [
                                'status' => 5,
                                'count' => $count,
                                'success_num' => $info,
                                'favorites_title' => "{$vo['favorites_title']}",
                                'msg' => "正在采集第{$count}个选库，共采集0条数据",
                            ];

                        }
                    }

                }

            }
            $this->collectMsg = [
                'status' => '2',
                'count' => $count,
                'total' => $this->total,
                'msg' => "本次采集结束，共采集{$count}个选库表,共{$this->total}条数据",
            ];
            $this->total = 0;

        }
        //return $this->collectMsg;
        return true;
    }


    /**
     * @param $data 处理的数据
     * @param int $is_qiang 是否咚咚抢
     * @param string $qiang_start_time  咚咚抢开始时间
     * @param string $qiang_end_time 咚咚抢结束时间
     * @param int $activityId  入库的活动ID
     * @return mixed
     */
    public function handleData($data,$activityId = 0, $is_qiang = 0 ,$qiang_start_time = '1970-01-01 00:00:00',$qiang_end_time = '1970-01-01 00:00:00')
    {
        $pattern = '/减(.*)元/';
        $category = $this->getCategory();
        $cate = Db::name('cate')
            ->field('id,cate_name,taobao_category')
            ->select();
        foreach ($data as $key => $value) {
            $data[$key]['cate_id'] = 0;
            $data[$key]['activity_id'] = $activityId;
            $data[$key]['is_qiang'] = $is_qiang;
            $data[$key]['qiang_start_time'] = $qiang_start_time;
            $data[$key]['qiang_end_time'] = $qiang_end_time;

            if (!isset($value['small_images'])) {
                $data[$key]['small_images'] = '';
            } else {
                if (!isset($value['small_images']['string'])) {
                    $data[$key]['small_images'] = '';
                } else {
                    $data[$key]['small_images'] = json_encode($value['small_images']['string'], JSON_UNESCAPED_UNICODE);
                }
            }

            if (!isset($value['coupon_click_url']) || !isset($value['coupon_end_time']) || !isset($value['coupon_info']) || !isset($value['coupon_start_time']) || !isset($value['coupon_total_count']) || !isset($value['coupon_remain_count'])) {
                unset($data[$key]);
            } else {
                preg_match($pattern, $data[$key]['coupon_info'], $matches);
                if (!empty($matches)) {
                    $data[$key]['coupon_info'] = $matches[1];
                }
                foreach ($category as $k => $v) {
                    if (in_array($value['category'], $v)) {
                        foreach ($cate as $item => $cateValue) {
                            if ($k == $cateValue['taobao_category']) {
                                $data[$key]['cate_id'] = $cateValue['id'];
                            }
                        }

                    }
                }
                ksort($data[$key]);
            }

        }
        return $data;
    }


    public function show()
    {
        $type = $this->request->param('type', '0', 'intval');
        $id = $this->request->param('id', '', 'intval');
        if (intval($type) === 1 && !empty($id)) {
            $url = url('collect/collectMsg', ['id' => $id, 'type' => 1]);
        } else {
            $url = url('collect/collectMsg', ['type' => 2]);
        }
        $this->assign('ajaxUrl', $url);
        return $this->fetch();
    }


    public function collectMsg()
    {
        if (empty($this->request->param('timed', '', 'intval'))) {
            return json([
                'status' => '1',
                'msg' => '参数请求错误'
            ]);
        }
        set_time_limit(0);//无限请求超时时间
        $type = $this->request->param('type', '1', 'intval');
        $id = $this->request->param('id', '', 'intval');
        if ($type == 1 && !empty($id)) {

            $res = Db::name('collect')
                ->alias('c')
                ->where(['c.id' => $id])
                ->field('c.favorites_id,c.cate_id,c.adzone_id,c.favorites_name,c.activity_id,a.is_qiang,a.start_time,a.end_time')
                ->join(config('database.prefix').'activity a','c.activity_id=a.id','left')
                ->find();
            if ($res) {
                $this->selectCollect($res);
                if (!empty($this->collectMsg)) {
                    return json($this->collectMsg);
                }
            } else {
                return json([
                    'status' => '4',
                    'msg' => '查询不到该采集规则'
                ]);
            }
        } elseif ($type == 2) {
            $this->autoCollect();
            if (!empty($this->collectMsg)) {
                return json($this->collectMsg);

            }

        } else {
            return json([
                'status' => '1',
                'msg' => '参数请求错误'
            ]);
        }

    }


}