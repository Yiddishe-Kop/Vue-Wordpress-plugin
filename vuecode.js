window.vEvent = new Vue();

Vue.component('button-cta', {
  props: {
    disabled: {
      required: false,
      default: false
    }
  },
  template: `<button @click="$emit('click')" :disabled="disabled" class="cta"><span class="button-label"><slot/></span></button>`
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
  props: ['title', 'desc'],
  template: `<div class="food-component">
                <div class="component-title">
                  <h5 class="serif">{{title}}</h5>
                  <p class="component-desc">{{desc}}</p>
                </div>

                <div class="component-details">
                  <slot/>
                </div>

                <div class="component-pills">
                  <p>pills</p>
                </div>
              </div>`
})

Vue.component('food-dropdown', {
  props: ['emptyText'],
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
  created() {
    vEvent.$on('foodOptionSelect', data => {
      this.selectedOption = data.itemName
      this.isOpen = false
    })
  }
})


Vue.component('dropdown-item', {
  props: ['name', 'price', 'image', 'inPackage', 'inSection', 'inComponent', 'itemId'],
  template: `<div @click="onSelect" class="dropdown-item">
                <img v-if="image" :src="image">
                <div>
                  <h5>{{name}}</h5>
                  <p>\${{price}}</p>
                </div>
             </div>`,
  methods: {
    onSelect() {
      vEvent.$emit(`${this.inPackage}foodOptionSelect`, {
        package: this.inPackage,
        section: this.inSection,
        component: this.inComponent,
        itemName: this.name,
        itemId: this.itemId,
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
  created() {
    vEvent.$on(`${this.packageName}foodOptionSelect`, data => {
      console.log('Selected: ', data)
      if (data.package == this.packageName) {
        this.packageData[data.section][data.component].selected.push(data.itemId)
      }
    })
  },
  mounted() {
    let package_data = JSON.parse(this.$refs.packageData.textContent)
    this.packageName = package_data.package_name
    this.packageData = package_data.sections_items
    console.log(this.$data);
  },
}
