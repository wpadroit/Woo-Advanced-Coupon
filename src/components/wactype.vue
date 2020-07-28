<template>
  <div>
    <input type="hidden" name="wac_main_nonce" :value="nonce" />
    <div class="wac-flex">
      <div class="wac-col-2">
        <div class="wac-form">
          <label for="discount">
            <strong>Discount Type</strong>
          </label>
          <select id="discount" v-model="value" @change="changeType" name="wac_coupon_type">
            <option
              v-for="(discount, index) in discounts"
              :key="'wac_coupon_type_'+index"
              :value="discount.value"
            >{{ discount.label }}</option>
          </select>
        </div>
      </div>
      <div class="wac-show-hide-checkbox">
        <div class="wac-form">
          <label>
            <strong>Discount Show ? Hide</strong>
            <div class="wac-checkbox">
              <label>
                <input
                  name="wac_discount_display"
                  type="radio"
                  value="show"
                  v-model="discount_display"
                /> Show
              </label>
              <label>
                <input
                  name="wac_discount_display"
                  type="radio"
                  value="hide"
                  v-model="discount_display"
                /> Hide
              </label>
            </div>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script type="text/javascript">
export default {
  props: ["nonce"],
  data() {
    return {
      value: "product",
      discount_display: "show",
      discounts: [
        { label: "Product Adjustment", value: "product" },
        { label: "Cart Adjustment", value: "cart" },
        { label: "Bulk Discount", value: "bulk" },
      ],
    };
  },
  created() {
    this.getData();
  },
  methods: {
    changeType() {
      this.$root.wac_form.type = this.value;
    },
    getData() {
      let formData = {
        action: "wac_get_main",
        post_id: wac_post.id,
      };
      let root = this;
      axios
        .post(wac_helper_obj.ajax_url, Qs.stringify(formData))
        .then((response) => {
          if (response.data != [] && response.data != "") {
            root.value = response.data.type;
            root.discount_display = response.data.discount_display;
            root.$root.wac_form.type = response.data.type;
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>
