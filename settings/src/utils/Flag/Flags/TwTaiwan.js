import * as React from "react";
const SvgTwTaiwan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TW_-_Taiwan_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#TW_-_Taiwan_svg__a)">
      <path
        fill="#EF0000"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TW_-_Taiwan_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#TW_-_Taiwan_svg__b)">
        <path fill="#2E42A5" d="M0 0v7h9V0H0Z" />
        <path
          fill="#FEFFFF"
          d="m4.365 5.405-.741.925-.18-1.171-1.103.43.43-1.104-1.171-.18.924-.74-.924-.741 1.17-.18-.43-1.103 1.105.43L3.624.8l.74.924L5.107.8l.18 1.17 1.103-.43-.43 1.105 1.17.179-.924.74.925.742-1.171.18.43 1.103-1.104-.43-.18 1.17-.74-.924Zm0-.409a1.431 1.431 0 1 0 0-2.862 1.431 1.431 0 0 0 0 2.862ZM5.51 3.565a1.145 1.145 0 1 1-2.29 0 1.145 1.145 0 0 1 2.29 0Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgTwTaiwan;
