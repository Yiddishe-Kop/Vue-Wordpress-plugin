Vue.productionTip = false;
window.vEvent = new Vue();

Vue.component('button-cta', {
  template: `<button @click="$emit('click')" class="cta"><slot/></button>`
})

Vue.component('pill', {
  template: `<span class="pill"><slot/></span>`
})

Vue.component('deluxe-switch', {
  props: ['label1', 'label2', 'selectedVal', 'id'],
  template: `<div class="pricing-switcher">
              <div class="fieldset">
                <input @change="$emit('change', label1)" type="radio" name="group-a" :value="label1" :id="id1" checked>
                <label :for="id1" :class="{active: selectedVal == label1}">{{label1}}</label>
                <input @change="$emit('change', label2)" type="radio" name="group-a" :value="label2" :id="id2">
                <label :for="id2" :class="{active: selectedVal == label2}">{{label2}}</label>
                <span class="switch" :class="{move: selectedVal == label2}"></span>
              </div>
            </div>`,
  data() {
    return {
      id1: 'opt1' + this.id,
      id2: 'opt2' + this.id
    }
  }
})

Vue.component('meal-section', {
  props: ['title', 'section'],
  template: `<div v-if="Object.keys(section).length" class="meal-section">
              <h3 class="section-title">{{title}}</h3>
              <div class="section-details">
                <slot>
                  <p class="empty"><small>Nothing here</small><br>Try the Deluxe Package!</p>
                </slot>
              </div>
            </div>`
})


Vue.component('food-component', {
  props: ['title', 'desc', 'comp', 'inPackage', 'inSection', 'isDeluxe'],
  template: `<div  class="food-component">
                <div class="component-title">
                  <h5 class="b">{{title}}</h5>
                  <p class="component-desc">{{desc}}</p>
                </div>

                <div class="component-details">
                  <slot/>
                </div>

                <div class="component-pills">
                  <pill>{{qtyIncluded}} included</pill>
                  <pill class="green" v-if="addedCost">+\${{addedCost}}</pill>
                </div>
              </div>`,
  data() {
    return {
      componentData: this.comp,
    }
  },
  computed: {
    selected() { return this.comp[this.isDeluxe ? 'selected_deluxe' : 'selected_basic'] },
    qtyIncluded() {
      return this.isDeluxe ? this.comp.info.qty_free_deluxe : this.comp.info.qty_free
    },
    addedCost() {
      let qtyFree = Number(this.qtyIncluded)
      if (this.selected.length > qtyFree) {
        let extras = this.selected.slice(qtyFree)
        let addedCost = 0;
        extras.forEach(id => {
          if (this.componentData.items[id.id]) { // not null
            addedCost += Math.round(Number(this.componentData.items[id.id].price)) * Number(id.qty)
          }
        })
        return addedCost
      } else {
        return 0
      }
    }
  }
})

Vue.component('food-dropdown', {
  props: ['emptyText', 'inPackage', 'inSection', 'inComponent', 'component', 'selection', 'addBtn', 'index', 'isDeluxe', 'numOfItems', 'isExtra'],
  template: `<div class="dropdown-wrapper">
                <div @click="isOpen = !isOpen" :class="{noSelection: !selection.id}" class="dropdown" ref="dropdownMenu">
                  {{label}}
                  <span v-if="canOpenDropdown" class="arrow-down-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" viewBox="0 0 24 24" class="icon-cheveron-selection"><path class="secondary" fill-rule="evenodd" d="M8.7 9.7a1 1 0 1 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 1 1-1.4 1.4L12 6.42l-3.3 3.3zm6.6 4.6a1 1 0 0 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 1.4-1.4l3.3 3.29 3.3-3.3z"/></svg>
                  </span>
                </div>
                <transition name="slide-in">
                  <div v-if="isOpen && canOpenDropdown" class="dropdown-options">
                    <slot/>
                  </div>
                </transition>
                <input v-if="isExtra" v-model="qty" type="number" min="1" class="form-control qty"/>
                <button-cta v-if="addBtn" @click="addLine" class="only-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22px" viewBox="0 0 24 24" class="icon-add-circle"><circle cx="12" cy="12" r="10" class="primary"/><path class="secondary" d="M13 11h4a1 1 0 0 1 0 2h-4v4a1 1 0 0 1-2 0v-4H7a1 1 0 0 1 0-2h4V7a1 1 0 0 1 2 0v4z"/></svg>
                </button-cta>
                <button-cta v-if="addBtn && component[isDeluxe? 'selected_deluxe' : 'selected_basic'].length > 1" @click="removeLine" class="only-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22px" viewBox="0 0 24 24" class="icon-remove-circle"><circle cx="12" cy="12" r="10" class="primary"/><rect width="12" height="2" x="6" y="11" class="secondary" rx="1"/></svg>
                </button-cta>
              </div>`,
  data() {
    return {
      isOpen: false,
      canOpenDropdown: true,
      qty: 1,
    }
  },
  watch: {
    qty(newQty, oldQty) {
      vEvent.$emit(`${this.inPackage}update_qty`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
        index: this.index,
        qty: newQty
      })
    }
  },
  computed: {
    label() {
      if (this.selection.id) {
        return this.component.items[this.selection.id].name
      } else {
        return this.emptyText
      }
    }
  },
  methods: {
    addLine() {
      vEvent.$emit(`${this.inPackage}addline`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
      })
    },
    removeLine() {
      vEvent.$emit(`${this.inPackage}removeline`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
      })
    },
    documentClick(e) {
      let el = this.$refs.dropdownMenu
      let target = e.target
      if ((el !== target) && !el.contains(target)) {
        this.isOpen = false
      }
    }
  },
  created() {
    document.addEventListener('click', this.documentClick)
  },
  mounted() {
    if (this.numOfItems == 1) {
      this.canOpenDropdown = false
      let items = this.component.items
      let item = items[Object.keys(items)[0]]
      vEvent.$emit(`${this.inPackage}foodoptionselect`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
        itemName: item.name,
        itemId: item.id,
        index: this.index,
        bothTiers: true
      })
    }
  },
  beforeDestroy() {
    document.removeEventListener('click', this.documentClick)
  }
})


Vue.component('dropdown-item', {
  props: ['item', 'isExtra', 'inPackage', 'inSection', 'inComponent', 'index'],
  template: `<div @click="onSelect" class="dropdown-item">
                <img v-if="item.image" :src="item.image">
                <div class="info">
                  <h5>{{item.name}}</h5>
                  <p v-if="isExtra">\${{Math.round(Number(item.price))}}</p>
                </div>
             </div>`,
  methods: {
    onSelect() {
      vEvent.$emit(`${this.inPackage}foodoptionselect`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
        itemName: this.item.name,
        itemId: this.item.id,
        index: this.index,
      })
    }
  }
})

var shabbosPackageMixin = {
  data() {
    return {
      category: undefined,
      packageName: null,
      packageId: null,
      imageGallery: undefined,
      stock: {
        manage: false,
        inStock: true,
        qty: 0
      },
      basePrice: {
        basic: 0,
        deluxe: 0
      },
      packageData: {},
      selectedPackage: 'Basic',
      minPeople: 2,
      quantity: 2,
      addToCartUrl: '',
      cartBtn: {
        class: 'success',
        html: 'Add to Cart',
      }
    }
  },
  computed: {
    isDeluxe() { return this.selectedPackage == 'Deluxe' },
    selectedVarName() { return this.isDeluxe ? 'selected_deluxe' : 'selected_basic' },
    packagePrice() {
      return this.isDeluxe ? this.basePrice.deluxe : this.basePrice.basic
    },
    extraPrice() {
      let sum = 0
      let extraItems = []
      for (let section in this.packageData) {
        for (let comp in this.packageData[section]) {
          let qtyFree = this.packageData[section][comp].info[this.isDeluxe ? 'qty_free_deluxe' : 'qty_free']
          let extras = this.packageData[section][comp][this.selectedVarName].slice(qtyFree)
          extras.forEach(id => {
            if (this.packageData[section][comp].items[id.id]) {
              sum += Math.round(Number(this.packageData[section][comp].items[id.id].price)) * Number(id.qty)
              extraItems.push(id.id + '/' + id.qty) // ID/QTY
            }
          })
        }
      }
      return {
        sum: sum,
        extraItems: extraItems.join(',')
      }
    },
    wooco_ids() {
      let selectedIds = {}
      for (let section in this.packageData) {
        for (let comp in this.packageData[section]) {
          let sel = this.packageData[section][comp][this.selectedVarName]
          sel.forEach(id => {
            if (id.id) { // not null
              if (!selectedIds.hasOwnProperty(id.id)) {
                selectedIds[id.id] = Number(id.qty)
              } else {
                selectedIds[id.id] += Number(id.qty)
              }
            }
          })
        }
      }
      let finalIds = [];
      for (let key in selectedIds) {
        finalIds.push(`${key}/${selectedIds[key]}`) // = ID/QTY
      }
      return finalIds.join(',')
    }
  },
  methods: {
    onDeluxeChange(e) {
      this.selectedPackage = e

      for (let section in this.packageData) {
        for (let component in this.packageData[section]) {
          this.packageData[section][component].selected =
            e == 'Basic' ?
              this.packageData[section][component].selected_basic :
              this.packageData[section][component].selected_deluxe;
        }
      }
    },
    addToCart() { // ajax add-to-cart
      if (!this.datepicker.date) {
        alert("Please select your date before continuing.")
      } else if (!this.quantity) {
        alert("Please enter amount of people.")
      } else if (this.cartBtn.html === 'Added to your cart!') {
        alert("This package is already in your cart.")
      } else {
        let data = {
          action: 'add_to_cart',
          product_id: this.packageId,
          wooco_ids: this.wooco_ids,
          is_deluxe: this.isDeluxe,
          wooco_total: this.packagePrice,
          wooco_extra: this.extraPrice.sum,
          wooco_extra_items: this.extraPrice.extraItems,
          quantity: 1,
          wooco_people: this.quantity,
          wooco_date: this.datepicker.date
        };
        jQuery.ajax({
          type: 'post',
          url: wc_add_to_cart_params.ajax_url,
          data: data,
          beforeSend: () => {
            this.cartBtn.class = 'loading'
            this.cartBtn.html = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>'
          },
          success: response => {
            if (response.error) {
              this.cartBtn.class = 'success'
              this.cartBtn.html = 'Error'
              console.warn({ response });
            } else {
              this.cartBtn.class = 'success'
              this.cartBtn.html = 'Added to your cart!'
              console.log({ response });
              jQuery('#mini-cart').html(response.fragments['div.widget_shopping_cart_content']);
            }
          },
        });
      }
    },
    shouldShowComp(showIn) {
      switch (showIn) {
        case 'both':
          return true;
        case 'deluxe':
          return this.isDeluxe;
        case 'basic':
          return !this.isDeluxe;
      }
    }
  },
  mounted() {
    let package_data = JSON.parse(this.$refs.packageData.textContent)

    this.imageGallery = package_data.image_gallery
    this.category = package_data.category
    this.packageName = package_data.package_name
    this.packageId = package_data.package_id
    this.stock = {
      manage: package_data.manage_stock,
      inStock: package_data.is_in_stock,
      qty: package_data.stock_qty,
    }
    this.basePrice = {
      basic: Math.round(Number(package_data.price) * package_data.percentage_hike),
      deluxe: Math.round(Number(package_data.deluxe_price) * package_data.percentage_hike),
    }
    this.addToCartUrl = package_data.addToCartUrl
    this.packageData = package_data.sections_items

    this.minPeople = package_data.min_people
    this.quantity = package_data.people >= this.minPeople ? package_data.people : this.minPeople
    if (package_data.date) this.datepicker.date = package_data.date

    // console.log(this.$data);

    // on DDselect
    vEvent.$on(`${this.packageName}foodoptionselect`, data => {
      console.log('Selected: ', data)
      if (data.bothTiers) {
        ['selected_deluxe', 'selected_basic'].forEach(tier => {
          if (this.packageData[data.section][data.component][tier][data.index]) {
            this.$set(
              this.packageData[data.section][data.component][tier][data.index], 'id', data.itemId
            )
          }
        })
      } else {
        this.$set(
          this.packageData[data.section][data.component][this.selectedVarName][data.index], 'id', data.itemId
        )
      }
    })
    // on update_qty
    vEvent.$on(`${this.packageName}update_qty`, data => {
      this.$set(
        this.packageData[data.section][data.component][this.selectedVarName][data.index], 'qty', data.qty
      )
    })
    // onAddLine
    vEvent.$on(`${this.packageName}addline`, data => {
      this.packageData[data.section][data.component][this.selectedVarName].push({ id: '', qty: 1 });
    })
    // onRemoveLine
    vEvent.$on(`${this.packageName}removeline`, data => {
      this.packageData[data.section][data.component][this.selectedVarName].pop();
    })
  },
}
