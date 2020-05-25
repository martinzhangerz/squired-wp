/**
 *
 * This store handles webflow data and makes api calls
 *
 */
import { writable } from 'svelte/store';
import { router } from './router';
import { ajaxCall } from '../utils/ajax';
import { notifications } from '../store/notifications';

const {
  hasToken,
  nonce,
  pages,
  collections,
  site,
  staticRules,
  dynamicRules,
  error,
  cacheDuration
} = window._wfAjaxData;

if (site) {
  site.dashboardUrl = `https://webflow.com/dashboard/sites/${site.shortName}/settings`
}

if (error) {
    router.goTo('/login');
    notifications.addNotification(error, "error");
} else {
  if (hasToken === 'true') {
    router.goTo('/configuration');
  } else {
    router.goTo('/login');
  }
}

function createWfDataStore() {
  const { subscribe, set, update } = writable({
    pages: pages || [],
    collections: collections || [],
    site: site || undefined,
    staticRules: staticRules || [],
    dynamicRules: dynamicRules || [],
    cacheDuration: cacheDuration || 0
  });

  return {
    subscribe,
    // Go To the defined path with the defined params
    saveToken: async token => {
      try {
        await ajaxCall({
          nonce,
          data: {
            token
          },
          action: 'save_wf_token'
        });

        const call = await ajaxCall({
          nonce,
          action: 'get_wf_site_data'
        });

        let res = await call.json();
        if (res.success) {
          // set data
          set({
            pages: res.data.pages || [],
            collections: res.data.collections || [],
            site: res.data.site,
            dynamicRules: [],
            staticRules: []
          });
          notifications.addNotification('Token Saved Successfully');
          // switch page
          router.goTo('/configuration');
        } else {
          // invalid token prob
          const error = res.data[0] || {
            code: '401',
            message: 'Invalid Token'
          };
          notifications.addNotification(error.message, 'error');
        }
      } catch (e) {
        // should be an issue with server
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save api token, please try again later'
        };
        notifications.addNotification(error.message, 'error');
      }
    },
    removeToken: async () => {
      try {
        await ajaxCall({
          nonce,
          action: 'remove_wf_token'
        });
        notifications.addNotification('Token removed successfully');
        window.location.reload();
        
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot remove token, please try again later'
        };
        notifications.addNotification(error.message, 'error');
      }
    },
    addStaticRule: (rule) => {
      update(data => {
        data.staticRules.push(rule);
        return {
          ...data
        };
      });
    },
    removeStaticRule: index => {
      update(data => {
        data.staticRules.splice(index, 1);
        return {
          ...data
        };
      });
    },
    addDynamicRule: (rule) => {
      update(data => {
        data.dynamicRules.push(rule); // [`/${slug}/*`, `/${slug}/`]
        return {
          ...data
        };
      });
    },
    removeDynamicRule: index => {
      update(data => {
        data.dynamicRules.splice(index, 1);
        return {
          ...data
        };
      });
    },
    saveStaticRules: async rules => {
      try {
        const call = await ajaxCall({
          action: 'save_wf_static_rules',
          nonce,
          data: {
            rules: JSON.stringify(rules)
          }
        });

        const res = await call.json();
        if (res.success) {
          const staticRules = res.data;
          update(n => {
            n.staticRules = staticRules;
            return {
              ...n
            };
          });
          notifications.addNotification(`Rules saved successfully`);
          return true;
        } else {
          const error = res.data[0] || {
            code: '500',
            message: 'Internal Server Error'
          };
          notifications.addNotification(error.message, 'error');
          return false;
        }
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save data please, try again later'
        };
        notifications.addNotification(error.message, 'error');
        return false;
      }
    },
    saveDynamicRules: async rules => {
      try {
        const call = await ajaxCall({
          action: 'save_wf_dynamic_rules',
          nonce,
          data: {
            rules: JSON.stringify(rules)
          }
        });

        const res = await call.json();
        if (res.success) {
          const dynamicRules = res.data;
          update(n => {
            n.dynamicRules = dynamicRules;
            return {
              ...n
            };
          });
          notifications.addNotification(`Rules saved successfully`);
          return true;
        } else {
          const error = res.data[0] || {
            code: '500',
            message: 'Internal Server Error'
          };
          notifications.addNotification(error.message, 'error');
          return false;
        }
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save data please, try again later'
        };
        notifications.addNotification(error.message, 'error');
        return false;
      }
    },
    invalidateCache: async () => {
      try {
        const call = await ajaxCall({
          action: 'invalidate_wf_cache',
          nonce
        });
        const res = await call.json();
        if (res.success) {
          notifications.addNotification(`Cache invalidated successfully`);
          return true;
        } else {
          const error = res.data[0] || {
            code: '500',
            message: 'Internal Server Error'
          };
          notifications.addNotification(error.message, 'error');
          return false;
        }
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save data please, try again later'
        };
        notifications.addNotification(error.message, 'error');
        return false;
      }
    },
    changeCache: async (duration) => {
      try {
        const call = await ajaxCall({
          action: 'change_wf_cache_duration',
          nonce,
          data: {
            duration
          }
        });
        const res = await call.json();
        if (res.success) {
          notifications.addNotification(`Cache settings changed successfully`);
          return true;
        } else {
          const error = res.data[0] || {
            code: '500',
            message: 'Internal Server Error'
          };
          notifications.addNotification(error.message, 'error');
          return false;
        }
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save data please, try again later'
        };
        notifications.addNotification(error.message, 'error');
        return false;
      }
    },
    preloadCache: async () => {
      try {
        const call = await ajaxCall({
          action: 'preload_wf_cache',
          nonce
        });
        const res = await call.json();
        if (res.success) {
          const pages = res.data || 0;
          notifications.addNotification(
            `Preloaded ${pages} ${pages == 1 ? 'page' : 'pages'}!`
          );
          return true;
        } else {
          const error = res.data[0] || {
            code: '500',
            message: 'Internal Server Error'
          };
          notifications.addNotification(error.message, 'error');
          return false;
        }
      } catch (e) {
        const error = {
          code: e.code || '500',
          message: e.message || 'Cannot save data please, try again later'
        };
        notifications.addNotification(error.message, 'error');
        return false;
      }
    }
  };
}

export const store = createWfDataStore();
