package com.sshtools.j2ssh.subsystem;

import java.io.IOException;

import com.sshtools.j2ssh.connection.Channel;
import com.sshtools.j2ssh.connection.SshMsgChannelData;
import com.sshtools.j2ssh.connection.SshMsgChannelExtendedData;
import com.sshtools.j2ssh.io.ByteArrayReader;
import com.sshtools.j2ssh.io.ByteArrayWriter;
import com.sshtools.j2ssh.io.DynamicBuffer;
import com.sshtools.j2ssh.transport.InvalidMessageException;

public abstract class SubsystemChannel extends Channel {

  Integer exitCode = null;
  String name;
  protected SubsystemMessageStore messageStore;
  DynamicBuffer buffer = new DynamicBuffer();
  int nextMessageLength = -1;

  public SubsystemChannel(String name) {
    this.name = name;
    this.messageStore = new SubsystemMessageStore();
  }

  public SubsystemChannel(String name, SubsystemMessageStore messageStore) {
    this.name = name;
    this.messageStore = messageStore;
  }

  @Override
public String getChannelType() {
    return "session";
  }

  protected void sendMessage(SubsystemMessage msg) throws
      InvalidMessageException, IOException {

    byte[] msgdata = msg.toByteArray();

    // Write the message length
    sendChannelData(ByteArrayWriter.encodeInt(msgdata.length));

    // Write the message data
    sendChannelData(msgdata);
  }


  @Override
protected void onChannelRequest(String requestType, boolean wantReply, byte[] requestData) throws java.io.IOException {

        if (requestType.equals("exit-status")) {
          exitCode = new Integer( (int) ByteArrayReader.readInt(requestData, 0));
        }
        else if (requestType.equals("exit-signal")) {
          ByteArrayReader bar = new ByteArrayReader(requestData);
          /*String signal =*/ bar.readString();
          /*boolean coredump =*/ bar.read() ;//!= 0;
          /*String message =*/ bar.readString();
          /*String language =*/ bar.readString();

          /*if (signalListener != null) {
            signalListener.onExitSignal(signal, coredump, message);
          }*/
        }
        else if (requestType.equals("xon-xoff")) {
          /*if (requestData.length >= 1) {
            localFlowControl = (requestData[0] != 0);
          }*/
        }
        else if (requestType.equals("signal")) {
          /*String signal =*/ ByteArrayReader.readString(requestData, 0);

          /*if (signalListener != null) {
            signalListener.onSignal(signal);
          }*/
        }
        else {
          if (wantReply) {
            connection.sendChannelRequestFailure(this);
          }
        }

  }

  @Override
protected void onChannelExtData(SshMsgChannelExtendedData msg) throws java.io.IOException {

  }

  @Override
protected void onChannelData(SshMsgChannelData msg) throws java.io.IOException {

    // Write the data to a temporary buffer that may also contain data
    // that has not been processed
    buffer.getOutputStream().write(msg.getChannelData());

    int read;
    byte[] tmp = new byte[4];
    byte[] msgdata;
    // Now process any outstanding messages
    while(buffer.getInputStream().available() > 4) {

      if(nextMessageLength==-1) {
        read = 0;
        while((read += buffer.getInputStream().read(tmp)) < 4);
        nextMessageLength = (int)ByteArrayReader.readInt(tmp, 0);
      }

      if(buffer.getInputStream().available() >= nextMessageLength) {
        msgdata = new byte[nextMessageLength];
        buffer.getInputStream().read(msgdata);
        messageStore.addMessage(msgdata);
        nextMessageLength = -1;
      } else
        break;
    }
  }


  @Override
protected void onChannelEOF() throws java.io.IOException {

  }
  
  @Override
  protected void onChannelClose() throws java.io.IOException {

  }

  @Override
  public byte[] getChannelOpenData() {
    return null;
  }

  @Override
  protected void onChannelOpen() throws java.io.IOException {

  }

  public boolean startSubsystem() throws IOException {

    ByteArrayWriter baw = new ByteArrayWriter();

    baw.writeString(name);

    return connection.sendChannelRequest(this, "subsystem", true,
                                      baw.toByteArray());

  }
  
  @Override
  public byte[] getChannelConfirmationData() {
    return null;
  }

}
