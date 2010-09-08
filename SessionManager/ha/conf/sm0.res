resource %RESOURCE% {
  device    %DEVICE%;
  disk      %LOOP%;
  meta-disk internal;

  disk {
    on-io-error   detach;
  }

  startup {
    wfc-timeout  10;
    degr-wfc-timeout 5;
  }

  syncer {
    # rate after al-extents use-rle cpu-mask verify-alg csums-alg
    rate 10M;
    verify-alg sha1;
  }

  net {
    cram-hmac-alg sha1;
    shared-secret "%AUTH_KEY%";
    after-sb-0pri discard-older-primary;
    after-sb-1pri call-pri-lost-after-sb;
    after-sb-2pri call-pri-lost-after-sb;
    #allow-two-primaries;
  }
  on %HOSTNAME% {
    address   %NIC_ADDR%:7788;
  }

}
