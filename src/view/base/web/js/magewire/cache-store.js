/**
 * MagewireCacheStore — IndexedDB wrapper with localStorage fallback.
 *
 * Provides a unified async key/value API for caching Magewire component
 * state on the client.  When IndexedDB is available it is preferred;
 * otherwise the store transparently degrades to localStorage.
 *
 * Public API
 * ----------
 *   get(key)          → Promise<value|null>
 *   put(key, value)   → Promise<void>          (create)
 *   post(key, value)  → Promise<void>          (update)
 *   delete(key)       → Promise<boolean>
 *   clear()           → Promise<void>
 *   keys()            → Promise<string[]>
 *
 * @internal This file is part of the Magewire cache feature.
 */
(function () {
    'use strict';

    var DB_NAME    = 'magewire_cache';
    var STORE_NAME = 'components';
    var DB_VERSION = 1;
    var LS_PREFIX  = 'wire_cache:';

    /* ------------------------------------------------------------------
     * IndexedDB back-end
     * ----------------------------------------------------------------*/

    function IDBBackend() {
        this._db = null;
    }

    IDBBackend.prototype.open = function () {
        var self = this;

        if (self._db) {
            return Promise.resolve(self._db);
        }

        return new Promise(function (resolve, reject) {
            var request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function (event) {
                var db = event.target.result;

                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME);
                }
            };

            request.onsuccess = function (event) {
                self._db = event.target.result;
                resolve(self._db);
            };

            request.onerror = function () {
                reject(request.error);
            };
        });
    };

    IDBBackend.prototype._tx = function (mode) {
        return this.open().then(function (db) {
            return db.transaction(STORE_NAME, mode).objectStore(STORE_NAME);
        });
    };

    IDBBackend.prototype._wrap = function (idbRequest) {
        return new Promise(function (resolve, reject) {
            idbRequest.onsuccess = function () { resolve(idbRequest.result); };
            idbRequest.onerror   = function () { reject(idbRequest.error); };
        });
    };

    IDBBackend.prototype.get = function (key) {
        var self = this;

        return self._tx('readonly').then(function (store) {
            return self._wrap(store.get(key));
        }).then(function (result) {
            return result === undefined ? null : result;
        });
    };

    IDBBackend.prototype.put = function (key, value) {
        var self = this;

        return self._tx('readwrite').then(function (store) {
            return self._wrap(store.put(value, key));
        }).then(function () {});
    };

    IDBBackend.prototype.post = function (key, value) {
        return this.put(key, value);
    };

    IDBBackend.prototype.delete = function (key) {
        var self = this;

        return self._tx('readwrite').then(function (store) {
            return self._wrap(store.delete(key));
        }).then(function () {
            return true;
        });
    };

    IDBBackend.prototype.clear = function () {
        var self = this;

        return self._tx('readwrite').then(function (store) {
            return self._wrap(store.clear());
        }).then(function () {});
    };

    IDBBackend.prototype.keys = function () {
        var self = this;

        return self._tx('readonly').then(function (store) {
            return self._wrap(store.getAllKeys());
        });
    };

    /* ------------------------------------------------------------------
     * localStorage back-end (fallback)
     * ----------------------------------------------------------------*/

    function LSBackend() {}

    LSBackend.prototype._key = function (key) {
        return LS_PREFIX + key;
    };

    LSBackend.prototype.get = function (key) {
        try {
            var raw = localStorage.getItem(this._key(key));
            return Promise.resolve(raw === null ? null : JSON.parse(raw));
        } catch (e) {
            return Promise.resolve(null);
        }
    };

    LSBackend.prototype.put = function (key, value) {
        try {
            localStorage.setItem(this._key(key), JSON.stringify(value));
        } catch (e) {
            // Storage full or unavailable — silently ignore.
        }

        return Promise.resolve();
    };

    LSBackend.prototype.post = function (key, value) {
        return this.put(key, value);
    };

    LSBackend.prototype.delete = function (key) {
        localStorage.removeItem(this._key(key));
        return Promise.resolve(true);
    };

    LSBackend.prototype.clear = function () {
        var toRemove = [];

        for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);

            if (k && k.indexOf(LS_PREFIX) === 0) {
                toRemove.push(k);
            }
        }

        for (var j = 0; j < toRemove.length; j++) {
            localStorage.removeItem(toRemove[j]);
        }

        return Promise.resolve();
    };

    LSBackend.prototype.keys = function () {
        var result = [];

        for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);

            if (k && k.indexOf(LS_PREFIX) === 0) {
                result.push(k.slice(LS_PREFIX.length));
            }
        }

        return Promise.resolve(result);
    };

    /* ------------------------------------------------------------------
     * Feature detection + public facade
     * ----------------------------------------------------------------*/

    function supportsIndexedDB() {
        try {
            if (typeof indexedDB === 'undefined' || !indexedDB) {
                return false;
            }

            // Safari private mode may throw on open — a quick probe
            // ensures we only use IDB when it actually works.
            var test = indexedDB.open('__mw_idb_probe');

            test.onsuccess = function () {
                test.result.close();
                indexedDB.deleteDatabase('__mw_idb_probe');
            };

            return true;
        } catch (e) {
            return false;
        }
    }

    var backend = supportsIndexedDB() ? new IDBBackend() : new LSBackend();

    /**
     * Unified cache store facade.
     *
     * Every method returns a Promise so consumers never have to care
     * which back-end is in use.
     *
     * @type {Object}
     */
    var MagewireCacheStore = {
        /**
         * Retrieve a value by key.
         *
         * @param  {string} key
         * @return {Promise<*|null>}
         */
        get: function (key) {
            return backend.get(key);
        },

        /**
         * Create (store) a value under the given key.
         *
         * @param  {string} key
         * @param  {*}      value  — must be structured-cloneable (IDB) / JSON-serialisable (LS).
         * @return {Promise<void>}
         */
        put: function (key, value) {
            return backend.put(key, value);
        },

        /**
         * Update (overwrite) a value under the given key.
         * Semantically identical to `put` but named separately
         * to mirror a REST-style create / update distinction.
         *
         * @param  {string} key
         * @param  {*}      value
         * @return {Promise<void>}
         */
        post: function (key, value) {
            return backend.post(key, value);
        },

        /**
         * Delete a single entry.
         *
         * @param  {string} key
         * @return {Promise<boolean>}
         */
        delete: function (key) {
            return backend.delete(key);
        },

        /**
         * Remove every entry from the store.
         *
         * @return {Promise<void>}
         */
        clear: function () {
            return backend.clear();
        },

        /**
         * List all stored keys.
         *
         * @return {Promise<string[]>}
         */
        keys: function () {
            return backend.keys();
        },

        /**
         * Name of the active storage driver.
         *
         * @return {'indexeddb'|'localstorage'}
         */
        get driver() {
            return backend instanceof IDBBackend ? 'indexeddb' : 'localstorage';
        }
    };

    // Expose globally so the feature phtml can pick it up.
    window.__magewire_cache_store = MagewireCacheStore;
})();