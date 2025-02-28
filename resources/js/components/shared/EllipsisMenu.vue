<template>
  <b-dropdown
    variant="ellipsis"
    no-caret
    no-flip
    lazy
    class="dropdown-right ellipsis-dropdown-main"
    @show="onShow"
    @hide="onHide"
    v-if="filterActions.length > 0"    
  >
    <template v-if="customButton" #button-content>
      <i
        class="pr-1 ellipsis-menu-icon no-padding"
        :class="customButton.icon"
      />
      <span>
        {{ customButton.content }} <b v-if="showProgress && data && data.batch"> {{ getTotalProgress(data.batch, data.progress) }}%</b>
      </span>
    </template>
    <template v-else #button-content>
      <i class="fas fa-ellipsis-h ellipsis-menu-icon" />
    </template>
    <div v-if="divider === true">
      <b-dropdown-item
        v-for="action in filterAboveDivider"
        :key="action.value"
        :href="action.link ? itemLink(action, data) : null"
        class="ellipsis-dropdown-item mx-auto"
        :data-test="action.dataTest"
        @click="!action.link ? onClick(action, data) : null"
      >
        <div class="ellipsis-dropdown-content">
          <i
            class="pr-1 fa-fw"
            :class="action.icon"
          />
          <span>{{ $t(action.content) }}</span>
        </div>
      </b-dropdown-item>
      <b-dropdown-divider />
      <b-dropdown-item
        v-for="action in filterBelowDivider"
        :key="action.value"
        :href="action.link ? itemLink(action, data) : null"
        class="ellipsis-dropdown-item mx-auto"
        @click="!action.link ? onClick(action, data) : null"
      >
        <div class="ellipsis-dropdown-content">
          <i
            class="pr-1 fa-fw"
            :class="action.icon"
          />
          <span>{{ $t(action.content) }}</span>
        </div>
      </b-dropdown-item>
    </div>
    <div v-else>
      <div v-for="action in filterActions">
        <b-dropdown-divider v-if="action.value == 'divider'"/>
        <b-dropdown-item v-else
            :key="action.value"
            :href="action.link ? itemLink(action, data) : null"
            class="ellipsis-dropdown-item mx-auto"
            @click="!action.link ? onClick(action, data) : null"
        >
          <div class="ellipsis-dropdown-content">
            <i
                class="pr-1 fa-fw"
                :class="action.icon"
            />
            <span>{{ $t(action.content) }}</span>
          </div>
        </b-dropdown-item>

      </div>
    </div>
  </b-dropdown>
</template>

<script>
import { Parser } from "expr-eval";
import Mustache from 'mustache';

export default {
  components: { },
  filters: { },
  mixins: [],
  props: ["actions", "permission", "data", "isDocumenterInstalled", "divider", "customButton", "showProgress"],
  data() {
    return {
      active: false,
    };
  },
  computed: {
    filterActions() {
      let btns = this.actions.filter(action => {
        if (!action.hasOwnProperty('permission')
            || action.hasOwnProperty('permission') && this.permission[action.permission]
            || Array.isArray(this.permission) && action.hasOwnProperty('permission') && this.permission.includes(action.permission)) {
          return action;
        }
      });

      btns = btns.filter(btn => {
        if (btn.hasOwnProperty('conditional') && btn.conditional === "isDocumenterInstalled") {
          if (this.isDocumenterInstalled) {
            return btn;
          }
        } else if (btn.hasOwnProperty('conditional') ) {
          const result = Parser.evaluate(btn.conditional, this.data);
          if (result) {
            return btn;
          }
        } else {
          return btn;
        }
      });
      return btns;
    },
    filterAboveDivider() {
      const filteredActions = this.filterActions;

      const firstActions = filteredActions.slice(0, -1);

      return firstActions;
    },
    filterBelowDivider() {
      const filteredActions = this.filterActions;

      const lastAction = filteredActions.slice(-1);

      return lastAction;
    },
  },
  methods: {
    onClick(action, data) {
      this.$emit("navigate", action, data);
    },
    itemLink(action, data) {
      return Mustache.render(action.href, data);
    },
    onShow() {
      this.$emit('show');
    },
    onHide() {
      this.$emit('hide');
    },

    getTotalProgress(batchProgress, chunkProgress) {
      const progressSlot = 100 / batchProgress.totalJobs;
      let totalProgress = batchProgress.progress;

      if (chunkProgress?.percentage > 0) {
        totalProgress += ((chunkProgress.percentage * progressSlot) / 100);
      }

      return Math.trunc(totalProgress);
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/colors";

.ellipsis-dropdown-main {
  float: right;
}

.ellipsis-dropdown-item {
    border-radius: 4px;
    width: 95%;
}

.ellipsis-dropdown-content {
    color: #42526E;
    font-size: 14px;
    margin-left: -15px;
}

.ellipsis-menu-icon.no-padding {
  padding: 0 !important;
}
</style>
