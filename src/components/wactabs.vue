<template>
  <div class="options_group">
    <p class="form-field">
      <label for="wac_feature">Coupon Feature</label>
      <select class="select short" name="wac_feature" id="wac_feature" v-model="wac_feature">
        <option value>Select Coupon Feature</option>
        <option
          v-for="(coupon, index) in coupons"
          :key="'coupon-'+index"
          :value="coupon.value"
        >{{ coupon.label }}</option>
      </select>
    </p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      wac_feature: "",
      coupons: [],
    };
  },
  created() {
    this.getLists();
    this.getData();
  },
  methods: {
    getLists() {
      let formData = {
        action: "wac_get_woocoupons",
        post_id: wac_post.id,
      };
      let root = this;
      axios
        .post(wac_helper_obj.ajax_url, Qs.stringify(formData))
        .then((response) => {
          root.coupons = response.data;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    getData() {
      let formData = {
        action: "wac_get_wac_panel",
        post_id: wac_post.id,
      };
      let root = this;
      axios
        .post(wac_helper_obj.ajax_url, Qs.stringify(formData))
        .then((response) => {
          root.wac_feature = response.data.list_id;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>