# 基于Discuz3.4的前端重构项目

	* 动态部分大体已经完成，部分细节还没有修改。
	* 分享部分的模板全部通过语言文件 lang_feed.php -> feed_share***  来实现。
	
## Bugs

    * 审核后的文章没有动态
    * 定期发布的文章没有动态
        
    * 非管理员用户使用富文本编辑器插入的图片和附件在提交文章后无显示。
    * 富文本编辑器中图片向画廊保存存在问题。
    
    *画廊图片下评论举报按钮 - 无法正常弹出表单
    
## 函数无优化

    * source/function/function_home.php -> getfollowfeed();
			

## 模板标记

    * 未完成部分搜索锚点 ： <!--[** search unfinished **]-->

			
#### 文章列表：
* 根据插件[1314]列表页缩略图 2.6.6 hook.class.php，增加文章列表图片显示。
* 列表样式优化
* 板块头部的简介和注意事项修复
		
#### 社团：
* 当前界面丑陋，将社团页面大图显示修改为rest风格
		
#### 广场：
* 广场订阅和专辑样式修改，增加更多按钮。
		
#### 消息通知部分：
* 不少删除，忽略（黑名单）的操作不能用
		
#### 全局：
* 悬浮菜单错位问题。
			
#### 个人空间：
* 逐步进行美化
			
#### 门户及门户管理
* 未开始
