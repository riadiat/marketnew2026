<?php
class ModelExtensionTotalCodFee extends Model {
	public function getTotal($total) {

		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] =='cod') {
			$this->load->language('extension/total/cod_fee');

			$code_fee = $this->config->get('total_cod_fee_price');

			$total['totals'][] = array(
				'code'       => 'cod_fee',
				'title'      => $this->language->get('text_code_fee'),
				'value'      => $code_fee,
				'sort_order' => $this->config->get('total_cod_fee_sort_order')
			);

			$total['total'] += $code_fee;
		}
	}
}