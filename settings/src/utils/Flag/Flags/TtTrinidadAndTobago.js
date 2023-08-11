import * as React from "react";
const SvgTtTrinidadAndTobago = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TT_-_Trinidad_and_Tobago_svg__a"
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
    <g mask="url(#TT_-_Trinidad_and_Tobago_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TT_-_Trinidad_and_Tobago_svg__b"
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
      <g mask="url(#TT_-_Trinidad_and_Tobago_svg__b)">
        <path
          fill="#272727"
          stroke="#F7FCFF"
          strokeWidth={0.732}
          d="m14.774 14.74-.265.218-.234-.25-15.172-16.2-.267-.285.303-.248L.687-3.291l.265-.217.234.25 15.172 16.2.267.285-.303.248-1.548 1.266Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgTtTrinidadAndTobago;
