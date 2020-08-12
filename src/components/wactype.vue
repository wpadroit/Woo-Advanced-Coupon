<template>
  <div>
    <div v-if="loading" class="spinner is-active wac_spinner"></div>
    <div v-else>
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

          <div class="wac-col-3 wac_buttons" v-if="value !== 'product'">
            <div class="wac-form">
              <label for="wac_discount_label">
                <strong>Discount Label</strong>
              </label>
              <input type="text" id="wac_discount_label" name="wac_discount_label" v-model="label" />
            </div>
          </div>
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
      loading: true,
      value: "product",
      label: null,
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
      this.loading = true;
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
            root.label = response.data.label;
            root.$root.wac_form.type = response.data.type;
          }
          root.loading = false;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>
