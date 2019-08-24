window.vEvent = new Vue();

Vue.component('button-cta', {
  props: {
    disabled: {
      required: false,
      default: false
    }
  },
  template: `<button @click="$emit('click')" :disabled="disabled"><slot/></button>`
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
  props: ['title'],
  template: `<div class="meal-section">
              <h3 class="section-title serif m">{{title}}</h3>
              <div class="section-details">
                <slot/>
              </div>
            </div>`
})


Vue.component('food-component', {
  props: ['title', 'desc', 'comp', 'inPackage', 'inSection'],
  template: `<div class="food-component">
                <div class="component-title">
                  <h5 class="serif">{{title}}</h5>
                  <p class="component-desc">{{desc}}</p>
                </div>

                <div class="component-details">
                  <slot/>
                </div>

                <div class="component-pills">
                  <pill class="green">{{qtyIncluded}} included</pill>
                  <pill class="red" v-if="addedCost">+\${{addedCost}}</pill>
                </div>
              </div>`,
  data() {
    return {
      componentData: this.comp,
      selected: this.comp.selected,
      qtyIncluded: this.comp.info.qty_free,
    }
  },
  computed: {
    addedCost() {
      let qtyFree = Number(this.qtyIncluded)
      if (this.selected.length > qtyFree) {
        let extras = this.selected.slice(qtyFree)
        let addedCost = 0;
        extras.forEach(id => {
          if(this.componentData.items[id]) { // not null
            addedCost += Number(this.componentData.items[id].price)
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
  props: ['emptyText', 'inPackage', 'inSection', 'inComponent', 'addBtn', 'index'],
  template: `<div class="dropdown-wrapper">
                <div @click="isOpen = !isOpen" class="dropdown">
                  {{label}}
                  <span class="arrow-down-icon">^</span>
                </div>
                <transition name="slide-in">
                  <div v-if="isOpen" class="dropdown-options">
                    <slot/>
                  </div>
                </transition>
                <button-cta v-if="addBtn" @click="addLine" class="only-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30px" viewBox="0 0 24 24" class="icon-add-circle"><circle cx="12" cy="12" r="10" class="primary"/><path class="secondary" d="M13 11h4a1 1 0 0 1 0 2h-4v4a1 1 0 0 1-2 0v-4H7a1 1 0 0 1 0-2h4V7a1 1 0 0 1 2 0v4z"/></svg>
                </button-cta>
                <button-cta v-if="addBtn" @click="removeLine" class="only-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30px" viewBox="0 0 24 24" class="icon-remove-circle"><circle cx="12" cy="12" r="10" class="primary"/><rect width="12" height="2" x="6" y="11" class="secondary" rx="1"/></svg>
                </button-cta>
              </div>`,
  data() {
    return {
      isOpen: false,
      selectedOption: null,
    }
  },
  computed: {
    label() {
      return this.selectedOption || this.emptyText
    }
  },
  methods: {
    addLine() {
      vEvent.$emit(`${this.inPackage}addline`,{
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
    }
  },
  created() {
    vEvent.$on(`${this.inPackage}foodoptionselect`, data => {
      if (data.package == this.inPackage
        && data.section == this.inSection
        && data.component == this.inComponent
        && data.index == this.index)
        this.selectedOption = data.itemName
      this.isOpen = false
    })
  }
})


Vue.component('dropdown-item', {
  props: ['name', 'price', 'image', 'inPackage', 'inSection', 'inComponent', 'itemId', 'index'],
  template: `<div @click="onSelect" class="dropdown-item">
                <img v-if="image" :src="image">
                <div>
                  <h5>{{name}}</h5>
                  <p>\${{price}}</p>
                </div>
             </div>`,
  methods: {
    onSelect() {
      // console.log(`${this.inPackage}foodOptionSelect`)
      vEvent.$emit(`${this.inPackage}foodoptionselect`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
        itemName: this.name,
        itemId: this.itemId,
        index: this.index,
      })
    }
  }
})

var shabbosPackageMixin = {
  data() {
    return {
      packageName: null,
      packageData: {},
      selectedPackage: 'Basic',
    }
  },
  mounted() {
    let package_data = JSON.parse(this.$refs.packageData.textContent)
    this.packageName = package_data.package_name
    this.packageData = package_data.sections_items
    console.log(this.$data);

    // onDDselect
    vEvent.$on(`${this.packageName}foodoptionselect`, data => {
      console.log('Selected: ', data)
      this.$set(
        this.packageData[data.section][data.component].selected, data.index, data.itemId
      )
      console.log(this.$data);
    })
    // onAddLine
    vEvent.$on(`${this.packageName}addline`, data => {
      this.packageData[data.section][data.component].selected.push(null);
    })
    // onRemoveLine
    vEvent.$on(`${this.packageName}removeline`, data => {
      this.packageData[data.section][data.component].selected.pop();
    })
  },
}
