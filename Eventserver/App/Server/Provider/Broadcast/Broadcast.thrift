namespace php Provider.Broadcast
namespace java com.jumei.java.eventcenter.if

/**
 * 消息中心的消息发送接口.
 * 
 * @author suchaoabc@163.com
 * @copyright www.jumei.com 
 * @created  2015-6-26 11:54:21 
 */
service Broadcast
{
    /**
     * 发送消息
     * @param string senderKey 消息中心用户名对应的key.
     * @param string secretKey 消息中心用户的个人密钥.
     * @param string messageClassKey 消息类型(名称)
     * @param string message
     * @param i32 priority 值越低优先级越高，一般情况下请使用1024.
     * @param i32 delay 延时推送（单位：秒).
     * @param string senderKey  name key of the sender. this param is currently used for recovering data only
     */
      bool send(1:string senderKey, 2:string secretKey, 3:string messageClassKey, 4:string message, 5:i32 priority, 6:i32 delay)

}