import * as React from "react";
const SvgAwAruba = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="AW_-_Aruba_svg__a"
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
    <g mask="url(#AW_-_Aruba_svg__a)">
      <path
        fill="#5BA3DA"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="AW_-_Aruba_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#AW_-_Aruba_svg__b)">
        <path
          fill="#EF2929"
          d="M2.78 4.29.242 3.536l2.595-.74.633-2.36.731 2.433 2.4.641-2.407.724-.718 2.324L2.78 4.29Z"
        />
        <path
          fill="red"
          d="M2.78 4.29.242 3.536l2.595-.74.633-2.36.731 2.433 2.4.641-2.407.724-.718 2.324L2.78 4.29Z"
        />
        <path fill="#FAD615" d="M16 7H0v1h16V7Zm0 2H0v1h16V9Z" />
      </g>
    </g>
  </svg>
);
export default SvgAwAruba;
