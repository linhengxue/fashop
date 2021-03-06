<?php
/**
 * 预存款日志数据模型
 *
 *
 *
 *
 * @copyright  Copyright (c) 2019 MoJiKeJi Inc. (http://www.fashop.cn)
 * @license    http://www.fashop.cn
 * @link       http://www.fashop.cn
 * @since      File available since Release v1.1
 */

namespace App\Model;




class PdLog extends Model
{
	protected $softDelete = true;

	/**
	 * 取日志总数
	 * @param array $condition
	 */
	public function getPdLogCount( $condition = [] )
	{
		return $this->where( $condition )->count();
	}

	/**
	 * 取得预存款变更日志列表
	 * @param array  $condition
	 * @param string $page
	 * @param string $field
	 * @param string $order
	 */
	public function getPdLogList( $condition = [], $fields = '*', $order = 'id desc', $page = [1,20] )
	{
		$data = $this->where( $condition )->field( $fields )->order( $order )->page( $page )->select();
		return $data;
	}

	/**
	 * 变更预存款
	 * @param array $change_type
	 * @param array $data
	 */
	public function changePd( $change_type, $data = [] )
	{
		$log_data                = [];
		$pd_data                 = [];
		$log_data['user_id']     = $data['user_id'];
		$log_data['username']    = $data['username'];
		$log_data['create_time'] = time();
		$log_data['pd_id']       = $data['pd_id'];
		$log_data['pd_sn']       = $data['pd_sn'];
		$log_data['state']       = $data['state'];
		$log_data['type']        = $change_type;
		$pd_data['type']         = $change_type;

		switch( $change_type ){
		case 'order_pay':
			$log_data['available_amount']    = - $data['amount'];
			$log_data['remark']              = '下单，支付预存款，订单号: '.$log_data['pd_sn'];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit-'.$data['amount']];
		break;
		case 'order_freeze':
			$log_data['available_amount']    = - $data['amount'];
			$log_data['freeze_amount']       = $data['amount'];
			$log_data['remark']              = '下单，冻结预存款，订单号: '.$log_data['pd_sn'];
			$pd_data['freeze_predeposit']    = ['exp', 'freeze_predeposit+'.$data['amount']];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit-'.$data['amount']];
		break;
		case 'order_cancel':
			$log_data['available_amount']    = $data['amount'];
			$log_data['freeze_amount']       = - $data['amount'];
			$log_data['remark']              = '取消订单，解冻预存款，订单号: '.$log_data['pd_sn'];
			$pd_data['freeze_predeposit']    = ['exp', 'freeze_predeposit-'.$data['amount']];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit+'.$data['amount']];
		break;
		case 'order_comb_pay':
			$log_data['freeze_amount']    = - $data['amount'];
			$log_data['remark']           = '下单，支付被冻结的预存款，订单号: '.$log_data['pd_sn'];
			$pd_data['freeze_predeposit'] = ['exp', 'freeze_predeposit-'.$data['amount']];
		break;
		case 'recharge':
			$log_data['available_amount']    = $data['amount'];
			$log_data['remark']              = '充值，充值单号: '.$log_data['pd_sn'];
			$log_data['admin_name']          = $data['admin_name'];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit+'.$data['amount']];
		break;
		case 'refund':
			$log_data['available_amount']    = $data['amount'];
			$log_data['remark']              = '确认退款，订单号: '.$log_data['pd_sn'];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit+'.$data['amount']];
		break;
		case 'cash_apply':
			$log_data['available_amount']    = - $data['amount'];
			$log_data['freeze_amount']       = $data['amount'];
			$log_data['remark']              = '申请提现，冻结预存款，提现单号: '.$log_data['pd_sn'];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit-'.$data['amount']];
			$pd_data['freeze_predeposit']    = ['exp', 'freeze_predeposit+'.$data['amount']];
		break;
		case 'cash_pay':
			$log_data['freeze_amount']    = - $data['amount'];
			$log_data['remark']           = '提现成功，提现单号: '.$log_data['pd_sn'];
			$log_data['admin_name']       = $data['admin_name'];
			$pd_data['freeze_predeposit'] = ['exp', 'freeze_predeposit-'.$data['amount']];
		break;
		case 'cash_del':
			$log_data['available_amount']    = $data['amount'];
			$log_data['freeze_amount']       = - $data['amount'];
			$log_data['remark']              = '取消提现申请，解冻预存款，提现单号: '.$log_data['pd_sn'];
			$log_data['admin_name']          = $data['admin_name'];
			$pd_data['available_predeposit'] = ['exp', 'available_predeposit+'.$data['amount']];
			$pd_data['freeze_predeposit']    = ['exp', 'freeze_predeposit-'.$data['amount']];
		break;
		default:
			throw new \Exception( lang( 'param_error' ) );
		break;
		}
		if( $change_type == 'recharge' && $log_data['state'] == 0 ){
			//充值下单情况

		} else{
			$update = \App\Model\User::init()->editUser( ['id' => $data['user_id']], $pd_data );
			if( !$update ){
				throw new \Exception( '操作失败' );
			}
		}

		trace( '1', 'debug' );
		trace( $log_data, 'debug' );
		$insert = $this->insertGetId( $log_data );

		trace( '2', 'debug' );
		if( !$insert ){
			throw new \Exception( '操作失败' );
		}
		return $insert;
	}

}
