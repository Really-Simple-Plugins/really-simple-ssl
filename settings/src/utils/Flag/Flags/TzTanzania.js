import * as React from "react";
const SvgTzTanzania = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TZ_-_Tanzania_svg__a"
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
    <g mask="url(#TZ_-_Tanzania_svg__a)">
      <path
        fill="#3195F9"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TZ_-_Tanzania_svg__b"
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
      <g mask="url(#TZ_-_Tanzania_svg__b)">
        <path
          fill="#73BE4A"
          fillRule="evenodd"
          d="M0 0v12L16 0H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#272727"
          stroke="#FFD018"
          strokeWidth={1.25}
          d="m-.91 12.72.346.52.52-.348L18.086.791l.52-.347-.347-.52-1.11-1.664-.347-.52-.52.348-18.13 12.101-.52.347.347.52 1.11 1.664Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgTzTanzania;
