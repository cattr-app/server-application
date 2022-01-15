<?php

/**
 * @apiDeprecated   since 4.0.0
 * @api             {get} /screenshot/:id Screenshot
 * @apiDescription  Get Screenshot
 *
 * @apiVersion      3.5.0
 * @apiName         Show
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiPermission   time_intervals_show
 * @apiPermission   time_intervals_full_access
 *
 * @apiParam {Integer}  id  ID of the target Time interval
 *
 * @apiSuccess {Raw}    data  Screenshot data
 *
 * @apiUse          400Error
 * @apiUse          ItemNotFoundError
 * @apiUse          ForbiddenError
 * @apiUse          UnauthorizedError
 */

/**
 * @apiDeprecated   since 4.0.0
 * @api             {get} /screenshot/:id Thumb
 * @apiDescription  Get Screenshot Thumbnail
 *
 * @apiVersion      3.5.0
 * @apiName         ShowThumb
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiPermission   time_intervals_show
 * @apiPermission   time_intervals_full_access
 *
 * @apiParam {Integer}  id  ID of the target Time interval
 *
 * @apiSuccess {Raw}    data  Screenshot data
 *
 * @apiUse          400Error
 * @apiUse          ItemNotFoundError
 * @apiUse          ForbiddenError
 * @apiUse          UnauthorizedError
 */

/**
 * @apiDeprecated   since 4.0.0
 * @api             {get,post} /screenshots/list List
 * @apiDescription  Get list of Screenshots
 *
 * @apiVersion      1.0.0
 * @apiName         List
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiPermission   screenshots_list
 * @apiPermission   screenshots_full_access
 *
 * @apiUse          UserParams
 *
 * @apiParamExample {json} Request Example
 *  {
 *      "id":               [">", 1],
 *      "time_interval_id": ["=", [1,2,3]],
 *      "user_id":          ["=", [1,2,3]],
 *      "project_id":       ["=", [1,2,3]],
 *      "path":             ["like", "%lorem%"],
 *      "created_at":       [">", "2019-01-01 00:00:00"],
 *      "updated_at":       ["<", "2019-01-01 00:00:00"]
 *  }
 *
 * @apiUse          UserObject
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  [
 *    {
 *      "id": 1,
 *      "time_interval_id": 1,
 *      "path": "uploads\/screenshots\/1_1_1.png",
 *      "created_at": "2020-01-23T09:42:26+00:00",
 *      "updated_at": "2020-01-23T09:42:26+00:00",
 *      "deleted_at": null,
 *      "thumbnail_path": null,
 *      "important": false,
 *      "is_removed": false
 *    },
 *    {
 *      "id": 2,
 *      "time_interval_id": 2,
 *      "path": "uploads\/screenshots\/1_1_2.png",
 *      "created_at": "2020-01-23T09:42:26+00:00",
 *      "updated_at": "2020-01-23T09:42:26+00:00",
 *      "deleted_at": null,
 *      "thumbnail_path": null,
 *      "important": false,
 *      "is_removed": false
 *    }
 *  ]
 *
 * @apiUse          400Error
 * @apiUse          UnauthorizedError
 * @apiUse          ForbiddenError
 */
/**
 * @apiDeprecated   since 4.0.0
 * @api             {post} /screenshots/create Create
 * @apiDescription  Create Screenshot
 *
 * @apiVersion      1.0.0
 * @apiName         Create
 * @apiGroup        Screenshot
 *
 * @apiParam {Integer}  time_interval_id  Time Interval ID
 * @apiParam {Binary}   screenshot        File
 *
 * @apiParamExample {json} Simple-Request Example
 *  {
 *    "time_interval_id": 1,
 *    "screenshot": <binary data>
 *  }
 *
 * @apiSuccess {Object}   res      User
 *
 * @apiUse          ScreenshotObject
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "res": {
 *      "id": 1,
 *      "time_interval_id": 1,
 *      "path": "uploads\/screenshots\/1_1_1.png",
 *      "created_at": "2020-01-23T09:42:26+00:00",
 *      "updated_at": "2020-01-23T09:42:26+00:00",
 *      "deleted_at": null,
 *      "thumbnail_path": null,
 *      "important": false,
 *      "is_removed": false
 *    }
 *  }
 *
 * @apiUse          400Error
 * @apiUse          ValidationError
 * @apiUse          UnauthorizedError
 * @apiUse          ForbiddenError
 */
/**
 * @apiDeprecated   since 4.0.0
 * @api             {post} /screenshots/remove Destroy
 * @apiDescription  Destroy Screenshot
 *
 * @apiVersion      1.0.0
 * @apiName         Destroy
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiPermission   screenshots_remove
 * @apiPermission   screenshots_full_access
 *
 * @apiParam {Integer}  id  ID of the target screenshot
 *
 * @apiParamExample {json} Request Example
 * {
 *   "id": 1
 * }
 *
 * @apiSuccess {String}   message  Destroy status
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "message": "Item has been removed"
 *  }
 *
 * @apiUse          400Error
 * @apiUse          ValidationError
 * @apiUse          ForbiddenError
 * @apiUse          UnauthorizedError
 */

/**
 * @apiDeprecated   since 1.0.0
 * @api             {post} /screenshots/bulk-create Bulk Create
 * @apiDescription  Create Screenshot
 *
 * @apiVersion      1.0.0
 * @apiName         Bulk Create
 * @apiGroup        Screenshot
 *
 * @apiPermission   screenshots_bulk_create
 * @apiPermission   screenshots_full_access
 */

/**
 * @apiDeprecated   since 4.0.0
 * @api             {get,post} /screenshot/count Count
 * @apiDescription  Count Screenshots
 *
 * @apiVersion      1.0.0
 * @apiName         Count
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiSuccess {String}   total    Amount of projects that we have
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "total": 2
 *  }
 *
 * @apiUse          400Error
 * @apiUse          ForbiddenError
 * @apiUse          UnauthorizedError
 */
/**
 * @apiDeprecated   since 4.0.0
 * @api             {post} /screenshots/show Show
 * @apiDescription  Show Screenshot
 *
 * @apiVersion      1.0.0
 * @apiName         Show
 * @apiGroup        Screenshot
 *
 * @apiUse          AuthHeader
 *
 * @apiPermission   screenshots_show
 * @apiPermission   screenshots_full_access
 *
 * @apiParam {Integer}  id ID
 *
 * @apiUse          ScreenshotParams
 *
 * @apiParamExample {json} Request Example
 *  {
 *    "id": 1,
 *    "time_interval_id": ["=", [1,2,3]],
 *    "path": ["like", "%lorem%"],
 *    "created_at": [">", "2019-01-01 00:00:00"],
 *    "updated_at": ["<", "2019-01-01 00:00:00"]
 *  }
 *
 * @apiUse          ScreenshotObject
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *   "id": 1,
 *   "time_interval_id": 1,
 *   "path": "uploads\/screenshots\/1_1_1.png",
 *   "created_at": "2020-01-23T09:42:26+00:00",
 *   "updated_at": "2020-01-23T09:42:26+00:00",
 *   "deleted_at": null,
 *   "thumbnail_path": null,
 *   "important": false,
 *   "is_removed": false
 *  }
 *
 * @apiUse          400Error
 * @apiUse          ValidationError
 * @apiUse          UnauthorizedError
 * @apiUse          ItemNotFoundError
 */
