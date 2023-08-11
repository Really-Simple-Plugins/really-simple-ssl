import * as React from "react";
const SvgTnTunisia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TN_-_Tunisia_svg__a"
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
    <g mask="url(#TN_-_Tunisia_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TN_-_Tunisia_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#TN_-_Tunisia_svg__b)">
        <path fill="#F7FCFF" d="M8 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path
          fill="#E31D1C"
          d="M8.701 8.825S6.593 8.25 6.593 5.987c0-2.264 2.108-2.888 2.108-2.888-.871-.338-3.423.18-3.423 2.888 0 2.707 2.623 3.195 3.423 2.838Zm-.116-3.33-1.046.381 1.125.393.037 1.053.685-.818 1.128.08-.813-.663.49-.957-.957.321-.662-.828.013 1.037Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgTnTunisia;
